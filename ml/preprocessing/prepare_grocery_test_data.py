import pandas as pd
import numpy as np

print("================================")
print("PREPARING KAGGLE TEST DATA")
print("================================")

# 1. Load Raw Data
raw_path = "ml/data/external/grocery_chain_sales_2023_2025.csv"
try:
    df = pd.read_csv(raw_path)
    print(f"Loaded raw dataset with {len(df)} rows.")
except Exception as e:
    print(f"Failed to load raw data: {e}")
    exit(1)

# 2. Cleaning (Phase B)
# Drop rows with missing store_name
df = df.dropna(subset=['store_name', 'product_name'])
print(f"Rows after dropping missing store/product names: {len(df)}")

# Convert quantity to numeric
df['quantity'] = pd.to_numeric(df['quantity'], errors='coerce')
df = df.dropna(subset=['quantity'])
df = df[df['quantity'] > 0]
print(f"Rows after validating quantity > 0: {len(df)}")

# Convert date
df['transaction_date'] = pd.to_datetime(df['transaction_date'], errors='coerce')
df = df.dropna(subset=['transaction_date'])

# 3. Test Identifiers (Phase D)
# Map store_name and product_name to external IDs to prevent collision with Tindahan data
stores = df['store_name'].unique()
products = df['product_name'].unique()

store_map = {name: idx+1 for idx, name in enumerate(stores)}
product_map = {name: idx+1 for idx, name in enumerate(products)}

df['external_store_id'] = df['store_name'].map(store_map)
df['external_product_id'] = df['product_name'].map(product_map)

# 4. Aggregate to Daily Demand (Phase C)
# Group by date, store, product
daily = df.groupby(['transaction_date', 'external_store_id', 'external_product_id']).agg({
    'quantity': 'sum',
    'aisle': 'first' # keep category
}).reset_index()
daily.rename(columns={'quantity': 'total_daily_quantity'}, inplace=True)
print(f"Aggregated daily demand records: {len(daily)}")

# 5. Season / Holiday Features (Phase E)
# Deterministic mapping based on date to mimic project expectations without fabricating real weather data
daily['month'] = daily['transaction_date'].dt.month
# Simple rule: Jan-May is Dry, Jun-Dec is Rainy
daily['season_category'] = np.where(daily['month'].isin([1, 2, 3, 4, 5]), 'Dry', 'Rainy')
daily['holiday_event'] = 'None' # Default to None to avoid fabricating arbitrary holidays

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

out_path = "ml/data/processed/grocery_store_daily_demand.csv"
daily.to_csv(out_path, index=False)
print(f"Saved processed dataset to {out_path}")
print("Preparation complete.")
