import pandas as pd
import numpy as np

print("========================================")
print("PREPARING TINDAHAN SALES TEST DATA")
print("========================================")

# 1. Load Raw Data (Sales sheet only)
raw_path = "ml/data/external/tindahan_sales_dataset.xlsx"
try:
    df = pd.read_excel(raw_path, sheet_name='Sales')
    print(f"Loaded raw dataset with {len(df)} rows.")
except Exception as e:
    print(f"Failed to load raw data: {e}")
    exit(1)

# 2. Cleaning & Validation
# Convert quantity and price to numeric
df['quantity'] = pd.to_numeric(df['quantity'], errors='coerce')
df['unit_price_php'] = pd.to_numeric(df['unit_price_php'], errors='coerce')
df = df.dropna(subset=['quantity', 'unit_price_php'])
df = df[(df['quantity'] > 0) & (df['unit_price_php'] > 0)]
print(f"Rows after validating quantity > 0 and price > 0: {len(df)}")

# Convert date
df['transaction_date'] = pd.to_datetime(df['date'], errors='coerce')
df = df.dropna(subset=['transaction_date'])

# 3. Test Identifiers
# The dataset is single-store. Assign a synthetic store_id (e.g. 9001)
external_store_id = 9001

# Map product_id (e.g. P001) to synthetic external_product_id (9001-9180) to avoid Tindahan collisions
products = df['product_id'].unique()
product_map = {name: idx + 9001 for idx, name in enumerate(products)}
df['external_store_id'] = external_store_id
df['external_product_id'] = df['product_id'].map(product_map)

# Keep category for reference later
category_map = df.drop_duplicates(subset=['external_product_id']).set_index('external_product_id')['category'].to_dict()

# 4. Aggregate to Daily Demand
daily = df.groupby(['transaction_date', 'external_store_id', 'external_product_id']).agg({
    'quantity': 'sum'
}).reset_index()
daily.rename(columns={'quantity': 'total_daily_quantity'}, inplace=True)

print(f"Aggregated daily demand records (raw): {len(daily)}")

# 5. Missing-Date / Zero-Demand Handling
# We ONLY zero-fill within a product's defensible active period (first sale to last sale).
# We do NOT create artificial history before the first sale or after the last sale.
all_records = []
for prod_id, group in daily.groupby('external_product_id'):
    min_date = group['transaction_date'].min()
    max_date = group['transaction_date'].max()
    # Create complete date range for this product's active period
    full_range = pd.date_range(start=min_date, end=max_date, freq='D')
    
    # Reindex group
    group = group.set_index('transaction_date').reindex(full_range).reset_index()
    group.rename(columns={'index': 'transaction_date'}, inplace=True)
    
    # Fill missing values
    group['external_store_id'] = external_store_id
    group['external_product_id'] = prod_id
    group['total_daily_quantity'] = group['total_daily_quantity'].fillna(0)
    all_records.append(group)

daily = pd.concat(all_records, ignore_index=True)
print(f"Aggregated daily demand records (after targeted zero-fill): {len(daily)}")

# Restore category for the output (optional, but matching kaggle structure)
daily['aisle'] = daily['external_product_id'].map(category_map)

# 6. Season / Holiday Features (Matching ml_historical_sales_view.php rules)
daily['month'] = daily['transaction_date'].dt.month
daily['day'] = daily['transaction_date'].dt.day

# Season category
conditions_season = [
    daily['month'].isin([3, 4, 5]),
    daily['month'].isin([6, 7, 8, 9, 10]),
    daily['month'].isin([11, 12, 1, 2])
]
choices_season = ['Summer', 'Rainy', 'Amihan']
daily['season_category'] = np.select(conditions_season, choices_season, default='Unknown')

# Holiday event
conditions_holiday = [
    (daily['month'] == 12) & (daily['day'] >= 16),
    (daily['month'] == 1) & (daily['day'] <= 2),
    (daily['month'] == 2) & (daily['day'].between(13, 15)),
    (daily['month'] == 3) & (daily['day'] >= 25),
    (daily['month'] == 4) & (daily['day'] <= 10),
    (daily['month'] == 5),
    (daily['month'] == 8) | ((daily['month'] == 9) & (daily['day'] <= 15)),
    (daily['month'] == 10) & (daily['day'] >= 30),
    (daily['month'] == 11) & (daily['day'] <= 2)
]
choices_holiday = [
    'Christmas', 'New Year', 'Valentines', 'Holy Week', 'Holy Week',
    'Fiesta', 'Back to School', 'Undas', 'Undas'
]
daily['holiday_event'] = np.select(conditions_holiday, choices_holiday, default='None')

# Reorder and save
final_cols = [
    'transaction_date', 
    'external_store_id', 
    'external_product_id', 
    'total_daily_quantity', 
    'season_category', 
    'holiday_event',
    'aisle'
]
daily = daily[final_cols]

out_path = "ml/data/processed/tindahan_sales_daily_demand.csv"
daily.to_csv(out_path, index=False)
print(f"Saved processed dataset to {out_path}")
print("Preparation complete.")
