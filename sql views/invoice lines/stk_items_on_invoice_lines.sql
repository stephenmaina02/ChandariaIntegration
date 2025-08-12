CREATE VIEW stk_items
AS
(
SELECT StockLink,Description_1, iUOMStockingUnitID FROM [HASBAH_NYERI].[dbo].StkItem
)