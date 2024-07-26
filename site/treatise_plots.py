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
    
    # The original code tried to calculate new genera by filtering out still alive genera (ending_date != 0).
    # This excluded genera that are still alive, leading to incorrect counts of new genera.
    # Group by beginning_date to count new genera
    new_genera = df.groupby('beginning_date').size().reset_index(name='new_genera')

    # Filter out rows where ending_date is 0 for extinct genera, this is because if it died before 0, that is def extict
    # But if it died at 0, I dont really know if it actually died or is still alive, since its present day
    # Global warming/humans may have killed it within just the past two centuries... after it lived for 1 million years +....
    extinct_genera = df[df['ending_date'] != 0].groupby('ending_date').size().reset_index(name='extinct_genera')

    # Determine the range of dates to consider
    all_dates = pd.concat([df['beginning_date'], df[df['ending_date'] != 0]['ending_date']]).unique()
    # Creates a unique list of all relevant dates for the analysis by combining beginning and ending dates.
    # 1. df['beginning_date']:
    #    - Extract the 'beginning_date' column from the DataFrame, aka the dates when genera first appeared.
    # 2. df[df['ending_date'] != 0]['ending_date']:
    #    - Filter the DataFrame to exclude rows where 'ending_date' is 0 (genera that are still alive).
    #    - Extract the 'ending_date' column from the filtered DataFrame.
    #    - This series contains the dates when genera went extinct, excluding those still alive.
    # 3. pd.concat([df['beginning_date'], df[df['ending_date'] != 0]['ending_date']]):
    #    - Concatenate the 'beginning_date' series and the filtered 'ending_date' series into a single series.
    #    - This combines all dates when genera appeared and dates when they went extinct (excluding those still alive).
    # 4. .unique():
    #    - Get the unique values from the concatenated series.
    #    - This ensures that each date is only included once, providing a unique list of dates for analysis.
    
    all_dates.sort()
    # Calculate total genera active on each date
    total_genera = pd.Series(index=all_dates, dtype=int).fillna(0)
    for date in all_dates:
        total_genera[date] = ((df['beginning_date'] >= date) & ((df['ending_date'] <= date) | (df['ending_date'] == 0))).sum()
        # For each date in the 'all_dates' series, calculate the total number of genera active on that date.
        # Boolean indexing filters 'df' and then sums the resulting boolean series to get the count.
        # 1. df['beginning_date'] >= date:
        #    - Check if the beginning date of a genera is greater than or equal to the current date.
        #    - This ensures we are considering genera that started before or exactly on the current date.
        # 2. (df['ending_date'] <= date) | (df['ending_date'] == 0):
        #    - Check if the ending date of a genera is less than or equal to the current date, or if the ending date is 0.
        #    - This ensures we are considering genera that ended before or exactly on the current date, or are still alive (ending_date == 0).
        # 3. (df['beginning_date'] >= date) & ((df['ending_date'] <= date) | (df['ending_date'] == 0)):
        #    - Combine the two conditions using the AND operator (&).
        #    - This filters the DataFrame to include only those genera that were active (started before or on the date and ended after or on the date, or are still alive).

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
    
    # below will be used to scale that colored portion denoting time periods
    y_max = total_genera['total_genera'].max()
    dynamic_height = 0.05 * y_max  # 5% of the maximum y value
    stage_y_position = -1 * dynamic_height  # Position them below the x-axis
    # Plot Total Genera
    fig1, ax1 = plt.subplots(figsize=(20, 8))
    ax1.plot(total_genera['date'], total_genera['total_genera'], color='b')
    ax1.set_xlim([max_date, min_date - figure_padding])
    ax1.set_title('Total Number of Genera per Date')
    ax1.set_xlabel('Date (Ma)')
    ax1.set_ylabel('Number of Genera')
    ax1.set_ylim(stage_y_position, y_max + dynamic_height)

    for stage, ((start, end), color) in stage_ranges.items():
        width = end - start
        ax1.add_patch(patches.Rectangle((start, stage_y_position), width, dynamic_height, color=color, alpha=0.7))
        text_x = start + width / 2
        ax1.text(text_x, stage_y_position + dynamic_height / 2, stage[0], ha='center', va='center', color='black', fontsize=12)
        # that weird formula for second argument is to make sure it is centered
    
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
    # below will be used to scale that colored portion denoting time periods
    y_max = new_genera['new_genera'].max()
    dynamic_height = 0.05 * y_max  # 5% of the maximum y value
    stage_y_position = -1 * dynamic_height  # Position them below the x-axis

    fig2, ax2 = plt.subplots(figsize=(20, 8))
    ax2.plot(new_genera['beginning_date'], new_genera['new_genera'], color='g')
    ax2.set_xlim([max_date, min_date - figure_padding])
    ax2.set_title('Number of New Genera per Date')
    ax2.set_xlabel('Date (Ma)')
    ax2.set_ylabel('Number of New Genera')
    ax2.set_ylim(stage_y_position, y_max + dynamic_height)
    
    for stage, ((start, end), color) in stage_ranges.items():
        width = end - start
        ax2.add_patch(patches.Rectangle((start, stage_y_position), width, dynamic_height, color=color, alpha=0.7))
        text_x = start + width / 2
        ax2.text(text_x, stage_y_position + dynamic_height / 2, stage[0], ha='center', va='center', color='black', fontsize=12)
    fig2.savefig('new_genera.png')
    
    # Plot Extinct Genera
    # below will be used to scale that colored portion denoting time periods
    y_max = extinct_genera['extinct_genera'].max()
    dynamic_height = 0.05 * y_max  # 5% of the maximum y value
    stage_y_position = -1 * dynamic_height  # Position them below the x-axis

    fig3, ax3 = plt.subplots(figsize=(20, 8))
    ax3.plot(extinct_genera['ending_date'], extinct_genera['extinct_genera'], color='r')
    ax3.set_xlim([max_date, min_date - figure_padding])
    ax3.set_title('Number of Extinct Genera per Date')
    ax3.set_xlabel('Date (Ma)')
    ax3.set_ylabel('Number of Extinct Genera')
    ax3.set_ylim(stage_y_position, y_max + dynamic_height)
    
    for stage, ((start, end), color) in stage_ranges.items():
        width = end - start
        ax3.add_patch(patches.Rectangle((start, stage_y_position), width, dynamic_height, color=color, alpha=0.7))
        text_x = start + width / 2
        ax3.text(text_x, stage_y_position + dynamic_height / 2, stage[0], ha='center', va='center', color='black', fontsize=12)
    fig3.savefig('extinct_genera.png')

if __name__ == "__main__":
    df = fetch_data()
    total_genera, new_genera, extinct_genera = process_data(df)
    plot_data(total_genera, new_genera, extinct_genera)
