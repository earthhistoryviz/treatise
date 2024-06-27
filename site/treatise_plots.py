import mysql.connector
import pandas as pd
import matplotlib.pyplot as plt
import matplotlib.patches as patches
from matplotlib.transforms import Bbox
import math
import numpy as np

def connect_to_db():
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='myDB'
    )

def fetch_data():
    conn = connect_to_db()
    query = """
    SELECT beginning_date, ending_date
    FROM fossil
    ORDER BY beginning_date DESC, ending_date DESC;
    """
    df = pd.read_sql(query, conn)
    conn.close()
    return df

def process_data(df):
    df['beginning_date'] = pd.to_numeric(df['beginning_date'], errors='coerce')
    df['ending_date'] = pd.to_numeric(df['ending_date'], errors='coerce')
    new_genera = df[df['ending_date'] > 0].groupby('beginning_date').size().reset_index(name='new_genera')
    extinct_genera = df[df['ending_date'] > 0].groupby('ending_date').size().reset_index(name='extinct_genera')
    # Determine the range of dates to consider
    all_dates = pd.concat([df['beginning_date'], df['ending_date']]).unique()
    all_dates.sort()
    # Calculate total genera active on each date
    total_genera = pd.Series(index=all_dates, dtype=int).fillna(0)
    for date in all_dates:
        total_genera[date] = ((df['beginning_date'] >= date) & ((df['ending_date'] <= date)) & df['ending_date'] > 0).sum()

    total_genera = total_genera.reset_index().rename(columns={'index': 'date', 0: 'total_genera'})

   # Define the bin edges and labels (midpoints)
    min_year = total_genera['date'].min()
    max_year = total_genera['date'].max()
    bin_edges = np.arange(min_year, max_year + 5, 5)
    bin_labels = (bin_edges[:-1] + bin_edges[1:]) / 2

    # Use pd.cut to bin the data according to the edges and assign the labels
    new_genera['beginning_date'] = pd.cut(new_genera['beginning_date'], bins=bin_edges, labels=bin_labels, right=False)
    extinct_genera['ending_date'] = pd.cut(extinct_genera['ending_date'], bins=bin_edges, labels=bin_labels, right=False)

    new_genera_grouped = new_genera.groupby('beginning_date').agg({
        'new_genera': 'sum'  # Only sum the 'new_genera' column
    }).reset_index()

    extinct_genera_grouped = extinct_genera.groupby('ending_date').agg({
        'extinct_genera': 'sum'  # Only sum the 'extinct_genera' column
    }).reset_index()

    new_genera_grouped = new_genera_grouped.dropna()
    extinct_genera_grouped = extinct_genera_grouped.dropna()

    return total_genera, new_genera_grouped, extinct_genera_grouped

def plot_data(total_genera, new_genera, extinct_genera):
    figure_padding = 10
    
    stage_ranges = {
        'Cambrian': ((541, 485.37), (153/255, 181/255, 117/255)),
        'Ordovician': ((485.37, 443.83), (51/255, 169/255, 126/255)),
        'Silurian': ((443.83, 419.2), (166/255, 220/255, 181/255)),
        'Devonian': ((419.2, 358.94), (229/255, 183/255, 90/255)),
        'Carboniferous': ((358.94, 298.88), (140/255, 176/255, 108/255)),
        'Permian': ((298.88, 251.9), (227/255, 99/255, 80/255)),
        'Triassic': ((251.9, 201.36), (164/255, 70/255, 159/255)),
        'Jurassic': ((201.36, 145.73), (78/255, 179/255, 211/255)),
        'Cretaceous': ((145.73, 66.04), (140/255, 205/255, 96/255)),
        'Paleogene': ((66.04, 23.04), (253/255, 108/255, 98/255)),
        'Neogene': ((23.04, 2.58), (255/255, 255/255, 51/255)),
        'Quaternary': ((2.58, 0 - figure_padding), (255/255, 237/255, 179/255))
    }
    
    all_dates = pd.concat([total_genera['date'], new_genera['beginning_date'], extinct_genera['ending_date']])
    min_date, max_date = all_dates.min(), all_dates.max()
    
    max_genera = max(total_genera['total_genera'].max(), new_genera['new_genera'].max(), extinct_genera['extinct_genera'].max())
    y_max = max_genera + 10
    y_min = -25 
    
    # Plot Total Genera
    fig1, ax1 = plt.subplots(figsize=(20, 8))
    ax1.plot(total_genera['date'], total_genera['total_genera'], color='b')
    ax1.set_xlim([max_date, min_date - figure_padding])
    ax1.set_title('Total Number of Genera per Date')
    ax1.set_xlabel('Date (Ma)')
    ax1.set_ylabel('Number of Genera')
    ax1.set_ylim(y_min, y_max)
    
    for stage, ((start, end), color) in stage_ranges.items():
        width = end - start
        ax1.add_patch(patches.Rectangle((start, -25), width, 25, color=color, alpha=0.7))
        text_x = start + width / 2
        ax1.text(text_x, -15, stage[0], ha='center', va='center', color='black', fontsize=12)
    
    ax1.annotate('Late Devonian Extinction\nCause: Possibly due to global cooling', xy=(380, 450), xytext=(0, 20),
                 textcoords='offset points', arrowprops=dict(arrowstyle='->', connectionstyle='arc3,rad=-.1'),
                 bbox=dict(boxstyle="round,pad=0.3", fc="white", ec="b", lw=2))

    ax1.annotate('Permian-Triassic Extinction\nCause: Possibly global warming', xy=(245, 250), xytext=(0, 50),
                    textcoords='offset points', arrowprops=dict(arrowstyle='->', connectionstyle='arc3,rad=-.5'),
                    bbox=dict(boxstyle="round,pad=0.3", fc="white", ec="b", lw=2))

    ax1.annotate('Late Ordovician Extinction\nCause: Possibly global warming', xy=(440, 250), xytext=(-115, 50),
                 textcoords='offset points', arrowprops=dict(arrowstyle='->', connectionstyle='arc3,rad=-.5'),
                 bbox=dict(boxstyle="round,pad=0.3", fc="white", ec="b", lw=2))
    ax1.annotate('End-Triassic Extinction\nCause: Possibly due to massive volcanism\nand associated climate change', xy=(201, 110), xytext=(-10, 50),
                 textcoords='offset points', arrowprops=dict(arrowstyle='->', connectionstyle='arc3,rad=.1'),
                 bbox=dict(boxstyle="round,pad=0.3", fc="white", ec="b", lw=2))

    ax1.annotate('Cretaceous-Paleogene (K-Pg) Extinction\nCause: Asteroid impact and volcanic activity', xy=(60, 70), xytext=(-100, 140),
                 textcoords='offset points', arrowprops=dict(arrowstyle='->', connectionstyle='arc3,rad=-.1'),
                 bbox=dict(boxstyle="round,pad=0.3", fc="white", ec="b", lw=2))
    
    fig1.savefig('total_genera.png')
    
    # Plot New Genera
    fig2, ax2 = plt.subplots(figsize=(20, 8))
    ax2.plot(new_genera['beginning_date'], new_genera['new_genera'], color='g')
    ax2.set_xlim([max_date, min_date - figure_padding])
    ax2.set_title('Number of New Genera per Date')
    ax2.set_xlabel('Date (Ma)')
    ax2.set_ylabel('Number of New Genera')
    ax2.set_ylim(-15)
    
    for stage, ((start, end), color) in stage_ranges.items():
        width = end - start
        ax2.add_patch(patches.Rectangle((start, -15), width, 15, color=color, alpha=0.7))
        text_x = start + width / 2
        ax2.text(text_x, -8, stage[0], ha='center', va='center', color='black', fontsize=12)
    fig2.savefig('new_genera.png')
    
    # Plot Extinct Genera
    fig3, ax3 = plt.subplots(figsize=(20, 8))
    ax3.plot(extinct_genera['ending_date'], extinct_genera['extinct_genera'], color='r')
    ax3.set_xlim([max_date, min_date - figure_padding])
    ax3.set_title('Number of Extinct Genera per Date')
    ax3.set_xlabel('Date (Ma)')
    ax3.set_ylabel('Number of Extinct Genera')
    ax3.set_ylim(-10)
    
    for stage, ((start, end), color) in stage_ranges.items():
        width = end - start
        ax3.add_patch(patches.Rectangle((start, -10), width, 10, color=color, alpha=0.7))
        text_x = start + width / 2
        ax3.text(text_x, -5, stage[0], ha='center', va='center', color='black', fontsize=12)
    fig3.savefig('extinct_genera.png')

if __name__ == "__main__":
    df = fetch_data()
    total_genera, new_genera, extinct_genera = process_data(df)
    plot_data(total_genera, new_genera, extinct_genera)
