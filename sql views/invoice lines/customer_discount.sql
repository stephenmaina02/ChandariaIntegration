CREATE VIEW customer_discount 
AS
(
select Percentage, Account, StockLink from [HASBAH_NYERI].[dbo].Client left join [HASBAH_NYERI].[dbo].[DrDiscHd] D on position=discmtrxrow 
LEFT JOIN  (SELECT STOCKLINK,CODE,Description,XPos,YPos,Percentage,ITEMGROUP FROM [HASBAH_NYERI].[dbo].DrDiscMx  INNER JOIN  [HASBAH_NYERI].[dbo].[GrpTbl] ON  [SMtrxCol]=YPos INNER JOIN   [HASBAH_NYERI].[dbo].StkItem ON ITEMGROUP=[StGroup]  ) AS MTRX ON XPos=position
)