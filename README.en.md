X-Cart user guide for Bitcoinus add-on integration

FIRST STEP

Install Bitcoinus add-on.

ENABLE PAYMENT METHOD

1. Select "Store Setup" > "Payment Methods"
2. Click "Add Payment Method"
3. Search for "Bitcoinus" and click ‘Install’
4. In payment method Settings page, fill in the required fields. Project ID and secret key are available from your Bitcoinus account (Project Settings)
5. Click "Save Changes"
6. Go to "Payment Methods" again
7. Activate Bitcoinus.

SET CALLBACK URL

1. Copy this URL to your Bitcoinus account’s project settings section "Callback URL",
https://example.com/xcart/cart.php?target=payment_return&txn_id_name=transactionID&transactionID=[ORDERID]
2. Change "example.com" to your e-commerce URL
3. Click "Save Changes".
