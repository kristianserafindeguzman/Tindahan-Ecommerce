import pandas as pd
import json
import sys
import os
import argparse

def main():
    parser = argparse.ArgumentParser(description='Extract Tindahan Sales XLSX for Laravel Seeder.')
    parser.add_argument('--input', type=str, required=True, help='Path to the input XLSX file')
    parser.add_argument('--output', type=str, required=True, help='Path to the output JSON file')
    args = parser.parse_args()

    input_path = args.input
    output_path = args.output

    if not os.path.exists(input_path):
        print(f"Error: Input file {input_path} does not exist.")
        sys.exit(1)

    try:
        # Load only the 'Sales' sheet
        df = pd.read_excel(input_path, sheet_name='Sales')
        
        # Ensure we have the required columns
        required_cols = [
            'transaction_id', 'date', 'time', 'product_name', 'category',
            'unit', 'quantity', 'unit_price_php', 'total_amount_php', 'customer_name'
        ]
        
        # Check if all required columns exist (case insensitive matching if needed, but let's assume exact match based on prior inspection)
        missing = [c for c in required_cols if c not in df.columns]
        if missing:
            print(f"Error: Missing columns in Sales sheet: {missing}")
            sys.exit(1)
            
        # Convert date and time to a single datetime string format
        df['date'] = pd.to_datetime(df['date']).dt.strftime('%Y-%m-%d')
        df['time'] = df['time'].astype(str)
        # some times might be full datetimes if Excel parsed them weirdly, grab the time part
        df['time'] = df['time'].apply(lambda x: x.split(' ')[-1] if ' ' in str(x) else str(x))
        
        # Ensure no NaNs in critical fields
        df = df.dropna(subset=['product_name', 'quantity', 'total_amount_php', 'unit_price_php', 'date'])
        
        # Convert numeric types safely
        df['quantity'] = pd.to_numeric(df['quantity'], errors='coerce').fillna(1).astype(int)
        df['unit_price_php'] = pd.to_numeric(df['unit_price_php'], errors='coerce').fillna(0.0)
        df['total_amount_php'] = pd.to_numeric(df['total_amount_php'], errors='coerce').fillna(0.0)
        
        # Replace NaN with None for JSON serialization
        df = df.where(pd.notna(df), None)
        
        records = df.to_dict(orient='records')
        
        # Ensure directory exists
        os.makedirs(os.path.dirname(os.path.abspath(output_path)), exist_ok=True)
        
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(records, f)
            
        print(f"Successfully extracted {len(records)} records to {output_path}")
        
    except Exception as e:
        print(f"Error processing excel file: {e}")
        sys.exit(1)

if __name__ == '__main__':
    main()
