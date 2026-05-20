# What are "Settlements" in a BNPL Platform?

In the Buy Now Pay Later (BNPL) industry, a **Settlement** (also called a Payout) is the actual transfer of money from the BNPL provider (TQSEET) to the Merchant's physical bank account.

### Why do Settlements exist?
When a shopper buys a product using TQSEET, they split the cost over time. However, **the merchant does not want to wait months to get paid**. 

To solve this, TQSEET takes on the risk and pays the merchant the full price of the item upfront (minus a small platform commission fee), usually within 24 to 48 hours. This bank transfer is the Settlement.

### Step-by-Step Example:
1. **The Order**: A customer buys a jacket for **1,000 DH** from the merchant's store using "Split in 4".
2. **Customer Checkout**: The customer pays the first installment of **250 DH** to TQSEET today. TQSEET will collect the remaining 750 DH over the next 3 months.
3. **The Commission**: TQSEET charges the merchant a 5% platform fee (50 DH).
4. **The Settlement**: Within 24 hours, TQSEET's finance team wires **950 DH** (1000 DH - 50 DH fee) directly to the merchant's business bank account.

### What is the "Settlements" Dashboard?
On platforms like Tabby and Klarna, the Settlements page allows the merchant to track these bank transfers. It usually has two tabs:
* **Upcoming**: A list of orders that have been completed by customers today, showing how much money TQSEET owes the merchant and when it will be wired to their bank.
* **Completed**: A historical ledger of past bank wires, allowing the merchant to download CSV receipts for their accounting and tax records.

Currently, TQSEET has an "Orders / Revenue" table, but we will eventually need a specific "Settlements" view where merchants can see the grouped bank wire transfers we send them.
