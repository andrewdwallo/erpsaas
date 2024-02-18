<?php

return [
    'default' => [
        'current_asset' => [
            'Cash and Cash Equivalents' => [
                'description' => 'The most liquid assets a company holds. This includes physical currency, bank balances, and short-term investments a company can quickly convert to cash.',
                'multi_currency' => true,
                'base_code' => '1000',
                'accounts' => [
                    'Cash on Hand' => [
                        'description' => 'The amount of money held by the company in the form of cash.',
                    ],
                ],
            ],
            'Receivables' => [
                'description' => 'Amounts owed to the company for goods sold or services rendered, including accounts receivable, notes receivable, and other receivables.',
                'multi_currency' => false,
                'base_code' => '1100',
                'accounts' => [
                    'Accounts Receivable' => [
                        'description' => 'The amount of money owed to the company by customers who have not yet paid for goods or services received.',
                    ],
                ],
            ],
            'Inventory' => [
                'description' => 'The raw materials, work-in-progress goods and completely finished goods that are considered to be the portion of a business\'s assets that are ready or will be ready for sale.',
                'multi_currency' => true,
                'base_code' => '1200',
            ],
            'Prepaid and Deferred Charges' => [
                'description' => 'Payments made in advance for future goods or services, such as insurance premiums, rent, and prepaid taxes.',
                'multi_currency' => false,
                'base_code' => '1300',
            ],
            'Other Current Assets' => [
                'description' => 'Other assets that are expected to be converted to cash, sold, or consumed within one year or the business\'s operating cycle.',
                'multi_currency' => true,
                'base_code' => '1400',
            ],
        ],
        'non_current_asset' => [
            'Long-Term Investments' => [
                'description' => 'Investments in securities like bonds and stocks, investments in other companies, or real estate held for more than one year, aiming for long-term benefits.',
                'multi_currency' => true,
                'base_code' => '1500',
            ],
            'Fixed Assets' => [
                'description' => 'Physical, tangible assets used in the business\'s operations with a useful life exceeding one year, such as buildings, machinery, and vehicles. These assets are subject to depreciation.',
                'multi_currency' => false,
                'base_code' => '1600',
            ],
            'Intangible Assets' => [
                'description' => 'Assets lacking physical substance but offering value to the business, like patents, copyrights, trademarks, software, and goodwill.',
                'multi_currency' => false,
                'base_code' => '1700',
            ],
            'Other Non-Current Assets' => [
                'description' => 'Includes long-term assets not classified in the above categories, such as long-term prepaid expenses, deferred tax assets, and loans made to other entities that are not expected to be settled within the next year.',
                'multi_currency' => true,
                'base_code' => '1800',
            ],
        ],
        'contra_asset' => [
            'Depreciation and Amortization' => [
                'description' => 'Accounts that accumulate depreciation of tangible assets and amortization of intangible assets, reflecting the reduction in value over time.',
                'multi_currency' => false,
                'base_code' => '1900',
            ],
            'Allowances for Receivables' => [
                'description' => 'Accounts representing estimated uncollectible receivables, used to adjust the value of gross receivables to a realistic collectible amount.',
                'multi_currency' => false,
                'base_code' => '1940',
            ],
            'Valuation Adjustments' => [
                'description' => 'Accounts used to record adjustments in asset values due to impairments, market changes, or other factors affecting their recoverable amount.',
                'multi_currency' => false,
                'base_code' => '1950',
            ],
        ],
        'current_liability' => [
            'Supplier Obligations' => [
                'description' => 'Liabilities arising from purchases of goods or services from suppliers, not yet paid for. This can include individual accounts payable and trade credits.',
                'multi_currency' => true,
                'base_code' => '2000',
                'accounts' => [
                    'Accounts Payable' => [
                        'description' => 'The amount of money owed by the company to suppliers for goods or services received.',
                    ],
                ],
            ],
            'Accrued Expenses and Liabilities' => [
                'description' => 'Expenses that have been incurred but not yet paid, including wages, utilities, interest, and taxes. This category can house various accrued expense accounts.',
                'multi_currency' => false,
                'base_code' => '2100',
                'accounts' => [
                    'Sales Tax Payable' => [
                        'description' => 'The amount of money owed to the government for sales tax collected from customers.',
                    ],
                ],
            ],
            'Short-Term Borrowings' => [
                'description' => 'Debt obligations due within the next year, such as bank loans, lines of credit, and short-term notes. This category can cover multiple short-term debt accounts.',
                'multi_currency' => true,
                'base_code' => '2200',
            ],
            'Customer Deposits and Advances' => [
                'description' => 'Funds received in advance for goods or services to be provided in the future, including customer deposits and prepayments.',
                'multi_currency' => true,
                'base_code' => '2300',
            ],
            'Other Current Liabilities' => [
                'description' => 'A grouping for miscellaneous short-term liabilities not covered in other categories, like the current portion of long-term debts, short-term provisions, and other similar obligations.',
                'multi_currency' => true,
                'base_code' => '2400',
            ],
        ],
        'non_current_liability' => [
            'Long-Term Borrowings' => [
                'description' => 'Obligations such as bonds, mortgages, and loans with a maturity of more than one year, covering various types of long-term debt instruments.',
                'multi_currency' => true,
                'base_code' => '2500',
            ],
            'Deferred Tax Liabilities' => [
                'description' => 'Taxes incurred in the current period but payable in a future period, typically due to differences in accounting methods between tax reporting and financial reporting.',
                'multi_currency' => false,
                'base_code' => '2600',
            ],
            'Other Long-Term Liabilities' => [
                'description' => 'Liabilities not due within the next year and not classified as long-term debt or deferred taxes, including pension liabilities, lease obligations, and long-term provisions.',
                'multi_currency' => true,
                'base_code' => '2700',
            ],
        ],
        'contra_liability' => [
            'Accumulated Amortization of Debt Discount' => [
                'description' => 'Accumulated amount representing the reduction of bond or loan liabilities, reflecting the difference between the face value and the discounted issuance price over time.',
                'multi_currency' => false,
                'base_code' => '2900',
            ],
            'Valuation Adjustments for Liabilities' => [
                'description' => 'Adjustments made to the recorded value of liabilities, such as changes in fair value of derivative liabilities or adjustments for hedging activities.',
                'multi_currency' => false,
                'base_code' => '2950',
            ],
        ],
        'equity' => [
            'Contributed Capital' => [
                'description' => 'Funds provided by owners or shareholders for starting the business and subsequent capital injections. Reflects the financial commitment of the owner(s) or shareholders to the business.',
                'multi_currency' => true,
                'base_code' => '3000',
                'accounts' => [
                    'Owner\'s Equity' => [
                        'description' => 'The owner\'s financial interest in the business, representing the residual interest in the assets of the business after deducting liabilities.',
                    ],
                ],
            ],
            'Retained Earnings' => [
                'description' => 'Cumulative profits retained in the business and not distributed as dividends. Indicates the company\'s financial health and profit-generating ability.',
                'multi_currency' => false,
                'base_code' => '3100',
            ],
            'Drawings' => [
                'description' => 'The amount of money taken out of the business by the owner(s) for personal use.',
                'multi_currency' => false,
                'base_code' => '3200',
            ],
            'Equity Reserves and Adjustments' => [
                'description' => 'Includes adjustments like revaluation reserves, foreign exchange adjustments, or other components of comprehensive income that affect the equity but are not classified under capital, retained earnings, or drawings.',
                'multi_currency' => true,
                'base_code' => '3300',
            ],
        ],
        'contra_equity' => [
            'Contra Equity' => [
                'description' => 'Equity that is deducted from gross equity to arrive at net equity. This includes treasury stock, which is stock that has been repurchased by the company.',
                'multi_currency' => false,
                'base_code' => '3900',
            ],
        ],
        'operating_revenue' => [
            'Product Sales' => [
                'description' => 'Income from selling physical or digital products. Includes revenue from all product lines or categories.',
                'multi_currency' => false,
                'base_code' => '4000',
                'accounts' => [
                    'Product Sales' => [
                        'description' => 'The amount of money earned from selling physical or digital products.',
                    ],
                ],
            ],
            'Service Revenue' => [
                'description' => 'Income earned from providing services, encompassing activities like consulting, maintenance, and repair services.',
                'multi_currency' => false,
                'base_code' => '4100',
            ],
            'Other Operating Revenue' => [
                'description' => 'Income from other business operations not classified as product sales or services, such as rental income, royalties, or income from licensing agreements.',
                'multi_currency' => false,
                'base_code' => '4200',
            ],
        ],
        'non_operating_revenue' => [
            'Investment Income' => [
                'description' => 'Earnings from investments, including dividends, interest from securities, and profits from real estate investments.',
                'multi_currency' => false,
                'base_code' => '4500',
                'accounts' => [
                    'Dividends' => [
                        'description' => 'The amount of money received from investments in shares of other companies.',
                    ],
                    'Interest Earned' => [
                        'description' => 'The amount of money earned from interest-bearing investments like bonds, certificates of deposit, or savings accounts.',
                    ],
                ],
            ],
            'Gains from Asset Disposition' => [
                'description' => 'Profits from selling assets like property, equipment, or investments, excluding regular sales of inventory.',
                'multi_currency' => false,
                'base_code' => '4600',
            ],
            'Other Non-Operating Revenue' => [
                'description' => 'Income from sources not related to the main business activities, such as legal settlements, insurance recoveries, or gains from foreign exchange transactions.',
                'multi_currency' => false,
                'base_code' => '4700',
                'accounts' => [
                    'Gain on Foreign Exchange' => [
                        'description' => 'The amount of money earned from foreign exchange transactions due to favorable exchange rate changes.',
                    ],
                ],
            ],
        ],
        'contra_revenue' => [
            'Contra Revenue' => [
                'description' => 'Revenue that is deducted from gross revenue to arrive at net revenue. This includes sales discounts, returns, and allowances.',
                'multi_currency' => false,
                'base_code' => '4900',
                'accounts' => [
                    'Sales Returns and Allowances' => [
                        'description' => 'The amount of money returned to customers or deducted from sales due to returned goods or allowances granted.',
                    ],
                    'Sales Discounts' => [
                        'description' => 'The amount of money deducted from sales due to discounts offered to customers for early payment or other reasons.',
                    ],
                ],
            ],
        ],
        'uncategorized_revenue' => [
            'Uncategorized Revenue' => [
                'description' => 'Revenue that has not been categorized into other revenue categories.',
                'multi_currency' => false,
                'base_code' => '4950',
                'accounts' => [
                    'Uncategorized Income' => [
                        'description' => 'Revenue from other business operations that don\'t fall under regular sales or services. This account is used as the default for all new transactions.',
                    ],
                ],
            ],
        ],
        'operating_expense' => [
            'Cost of Goods Sold' => [
                'description' => 'Direct costs attributable to the production of goods sold by a company. This includes material costs and direct labor.',
                'multi_currency' => false,
                'base_code' => '5000',
            ],
            'Payroll and Employee Benefits' => [
                'description' => 'Expenses related to employee compensation, including salaries, wages, bonuses, commissions, and payroll taxes.',
                'multi_currency' => false,
                'base_code' => '5050',
                'accounts' => [
                    'Salaries and Wages' => [
                        'description' => 'The amount of money paid to employees for their work, including regular salaries and hourly wages.',
                    ],
                    'Payroll Employer Taxes and Contributions' => [
                        'description' => 'The amount of money paid by the employer for payroll taxes and contributions, such as social security, unemployment, and workers\' compensation.',
                    ],
                    'Employee Benefits' => [
                        'description' => 'The amount of money spent on employee benefits, such as health insurance, retirement plans, and other benefits.',
                    ],
                    'Payroll Processing Fees' => [
                        'description' => 'The amount of money paid to third-party payroll processors for payroll services.',
                    ],
                ],
            ],
            'Facility Expenses' => [
                'description' => 'Costs incurred for business premises, including rent or lease payments, property taxes, utilities, and building maintenance.',
                'multi_currency' => false,
                'base_code' => '5100',
                'accounts' => [
                    'Rent or Lease Payments' => [
                        'description' => 'The amount of money paid for renting or leasing business premises.',
                    ],
                    'Property Taxes' => [
                        'description' => 'The amount of money paid for taxes on business property.',
                    ],
                    'Building Maintenance' => [
                        'description' => 'The amount of money spent on maintaining business premises, including repairs and cleaning.',
                    ],
                    'Utilities' => [
                        'description' => 'The amount of money paid for business utilities, such as electricity, water, and gas.',
                    ],
                    'Property Insurance' => [
                        'description' => 'The amount of money paid for insurance on business property.',
                    ],
                ],
            ],
            'General and Administrative' => [
                'description' => 'Expenses related to general business operations, such as office supplies, insurance, and professional fees.',
                'multi_currency' => false,
                'base_code' => '5150',
                'accounts' => [
                    'Food and Drink' => [
                        'description' => 'The amount of money spent on food and drink for business purposes, such as office snacks, meals, and catering.',
                    ],
                    'Transportation' => [
                        'description' => 'The amount of money spent on business transportation, such as fuel, vehicle maintenance, and public transportation.',
                    ],
                    'Travel' => [
                        'description' => 'The amount of money spent on business travel, such as airfare, hotels, and rental cars.',
                    ],
                    'Entertainment' => [
                        'description' => 'The amount of money spent on business entertainment, such as client dinners, events, and tickets.',
                    ],
                    'Office Supplies' => [
                        'description' => 'The amount of money spent on office supplies, such as paper, ink, and stationery.',
                    ],
                    'Office Equipment and Furniture' => [
                        'description' => 'The amount of money spent on office equipment and furniture, such as computers, printers, and desks.',
                    ],
                    'Legal and Professional Fees' => [
                        'description' => 'The amount of money paid for legal and professional services, such as legal advice, accounting, and consulting.',
                    ],
                    'Software and Subscriptions' => [
                        'description' => 'The amount of money spent on software and subscriptions, such as SaaS products, cloud services, and digital tools.',
                    ],
                ],
            ],
            'Marketing and Advertising' => [
                'description' => 'Expenses related to marketing and advertising activities, such as advertising campaigns, promotional events, and marketing materials.',
                'multi_currency' => false,
                'base_code' => '5200',
                'accounts' => [
                    'Advertising' => [
                        'description' => 'The amount of money spent on advertising campaigns, including print, digital, and outdoor advertising.',
                    ],
                    'Marketing' => [
                        'description' => 'The amount of money spent on marketing activities, such as content creation, social media, and email marketing.',
                    ],
                ],
            ],
            'Research and Development' => [
                'description' => 'Expenses incurred in the process of researching and developing new products or services.',
                'multi_currency' => false,
                'base_code' => '5250',
            ],
            'Other Operating Expenses' => [
                'description' => 'Miscellaneous expenses not categorized elsewhere, such as research and development costs, legal fees, and other irregular expenses.',
                'multi_currency' => false,
                'base_code' => '5300',
            ],
        ],
        'non_operating_expense' => [
            'Interest and Financing Costs' => [
                'description' => 'Expenses related to borrowing and financing, such as interest payments on loans, bonds, and credit lines.',
                'multi_currency' => false,
                'base_code' => '5500',
            ],
            'Tax Expenses' => [
                'description' => 'Various taxes incurred by the business, including income tax, sales tax, property tax, and payroll tax.',
                'multi_currency' => false,
                'base_code' => '5600',
            ],
            'Other Non-Operating Expense' => [
                'description' => 'Expenses not related to primary business activities, like losses from asset disposals, legal settlements, restructuring costs, or foreign exchange losses.',
                'multi_currency' => false,
                'base_code' => '5700',
                'accounts' => [
                    'Loss on Foreign Exchange' => [
                        'description' => 'The amount of money lost from foreign exchange transactions due to unfavorable exchange rate changes.',
                    ],
                ],
            ],
        ],
        'contra_expense' => [
            'Contra Expenses' => [
                'description' => 'Expenses that are deducted from gross expenses to arrive at net expenses. This includes purchase discounts, returns, and allowances.',
                'multi_currency' => false,
                'base_code' => '5900',
                'accounts' => [
                    'Purchase Returns and Allowances' => [
                        'description' => 'The amount of money returned to suppliers or deducted from purchases due to returned goods or allowances granted.',
                    ],
                    'Purchase Discounts' => [
                        'description' => 'The amount of money deducted from purchases due to discounts offered by suppliers for early payment or other reasons.',
                    ],
                ],
            ],
        ],
        'uncategorized_expense' => [
            'Uncategorized Expense' => [
                'description' => 'Expenses that have not been categorized into other expense categories.',
                'multi_currency' => false,
                'base_code' => '5950',
                'accounts' => [
                    'Uncategorized Expense' => [
                        'description' => 'Expenses not classified into regular expense categories. This account is used as the default for all new transactions.',
                    ],
                ],
            ],
        ],
    ],
    'category_account_map' => [
        'Dividends' => 'Dividends',
        'Interest Earned' => 'Interest Earned',
        'Wages' => 'Salaries and Wages',
        'Sales' => 'Product Sales',
        'Other Income' => 'Uncategorized Income',
        'Rent or Mortgage' => 'Rent or Lease Payments',
        'Utilities' => 'Utilities',
        'Groceries' => 'Food and Drink',
        'Transportation' => 'Transportation',
        'Other Expenses' => 'Uncategorized Expense',
    ],
];
