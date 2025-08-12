CREATE VIEW tax_rate_type
AS
(
SELECT  t.idTaxRate, s.StockLink FROM [HASBAH_NYERI].[dbo].TaxRate t, [HASBAH_NYERI].[dbo].StkItem s WHERE s.TTI = T.idTaxRate
)