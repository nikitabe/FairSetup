USE [FairSetup]
GO
/****** Object:  Trigger [dbo].[UpdateFinalNumbers]    Script Date: 11/30/2020 12:53:46 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO




ALTER TRIGGER [dbo].[UpdateFinalNumbers]
   ON  [dbo].[eval_cycle_details]
   AFTER UPDATE
AS 
BEGIN
	-- SET NOCOUNT ON added to prevent extra result sets from
	-- interfering with SELECT statements.
	SET NOCOUNT ON;

	IF	   UPDATE( rate_hourly_impact ) 
		OR UPDATE( TimeSpentSeconds ) 
		OR UPDATE( TimeSpentSeconds_AsCash ) 
		OR UPDATE( impact_request ) 
		OR UPDATE( impact_money) 
		OR UPDATE( rate_hourly_cash_out ) 
		OR UPDATE( reverse_investment ) 
		OR UPDATE( commission_impact ) 
		OR UPDATE( commission_reverse ) 



	BEGIN	

		update eval_cycle_details set 
			final_impact_to_apply = 
						-- Impact as a partner is not applied
						 ISNULL( inserted.rate_hourly_impact, 0 ) * (ISNULL( inserted.TimeSpentSeconds, 0 )/60./60)  -- Impact from hourly activity
						 + ISNULL( inserted.impact_request, 0 )  
						 + ISNULL( inserted.commission_impact, 0) 
						 + ISNULL( inserted.commission_reverse, 0) -- Commission taken out is recognized as investment

			,final_cash_out = 
						   ISNULL( inserted.rate_hourly_cash_out, 0 ) * (ISNULL( inserted.TimeSpentSeconds_AsCash, ISNULL( inserted.TimeSpentSeconds, 0 ) )/60./60)  -- Amount being taken out
						 + ISNULL( inserted.reverse_investment, 0 )
						 + ISNULL( inserted.commission_reverse, 0)
		from inserted inner join View_Eval_Cycle_Details vd on inserted.EvaluationItemID = vd.EvaluationItemID

		update eval_cycle_details set 
			final_impact_in =  eval_cycle_details.final_impact_to_apply 
				+ ISNULL( vd.HourlyImpact, 0 ) * (ISNULL( inserted.TimeSpentSeconds, 0 )/60./60)  -- Impact as a partner
				+ ISNULL( inserted.impact_money, 0) -- Applied separately
		from inserted inner join View_Eval_Cycle_Details vd on inserted.EvaluationItemID = vd.EvaluationItemID
		where inserted.EvaluationItemID = eval_cycle_details.EvaluationItemID 

		IF EXISTS (SELECT * FROM DELETED)
			update eval_cycle_details set 
			TimeApplied = ISNULL( inserted.TimeApplied, GETDATE() ) -- if this is the first time we are inserting, let's update the time 
			from inserted inner join View_Eval_Cycle_Details vd on inserted.EvaluationItemID = vd.EvaluationItemID

	END

END
GO

ALTER TABLE user_status
	add IsActive bit

GO

USE [FairSetup]
GO

/****** Object:  View [dbo].[View_CompanyUsers]    Script Date: 12/9/2020 10:53:56 PM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO


ALTER VIEW [dbo].[View_CompanyUsers]
AS
SELECT        dbo.users.UserID, dbo.users.username, dbo.users.password, dbo.users.FullName, dbo.users.email, dbo.users.ResetPassKey, dbo.users.AccountCode, dbo.users.DateCreated, dbo.users.BankRoutingNumber, 
                         dbo.users.BankAccountNumbre, dbo.users.VenmoAccount, dbo.users.PaypalEmail, dbo.users.CheckAddress1, dbo.users.CheckAddress2, dbo.users.LastCompanyID, dbo.user_to_company.CompanyID, 
                         dbo.user_to_company.Role, dbo.user_to_company.NameInCompany, dbo.user_to_company.EmailInCompany, dbo.user_to_company.exclude, dbo.user_to_group.GroupID, CAST(dbo.user_groups.Name AS VARCHAR(100)) 
                         AS GroupName, dbo.user_status.StatusName AS Status, dbo.user_status.StatusID, dbo.companies.Name AS CompanyName, dbo.companies.archived, user_status.IsActive
FROM            dbo.companies INNER JOIN
                         dbo.user_to_company INNER JOIN
                         dbo.users ON dbo.user_to_company.UserID = dbo.users.UserID ON dbo.companies.CompanyID = dbo.user_to_company.CompanyID LEFT OUTER JOIN
                         dbo.user_status ON dbo.user_to_company.StatusID = dbo.user_status.StatusID LEFT OUTER JOIN
                         dbo.user_groups INNER JOIN
                         dbo.user_to_group ON dbo.user_groups.GroupID = dbo.user_to_group.GroupID ON dbo.users.UserID = dbo.user_to_group.UserID AND dbo.user_to_group.CompanyID = dbo.user_to_company.CompanyID


GO


ALTER PROCEDURE [dbo].[InitTableData]
AS
BEGIN

DELETE FROM algorithms

INSERT INTO algorithms (AlgorithmID, Name, Description) VALUES ( 0, 'Bonus', 'Original Calculation for bonuses' )
INSERT INTO algorithms (AlgorithmID, Name, Description) VALUES ( 1, 'Equity 1 (Momentum, logistic)', 'Calculation of impact potential using logistic.' )
INSERT INTO algorithms (AlgorithmID, Name, Description) VALUES ( 
	2, 
	'Equity 2 (Momentum, linear)', 
	'Calculation of impact using linear potential.' )
INSERT INTO algorithms (AlgorithmID, Name, Description) VALUES ( 
	3, 
	'Equity 3 (Momentum, linear, discrete)', 
	'Calculation of impact using linear potential, discrete investments.' )

SET IDENTITY_INSERT dbo.user_status ON
INSERT into user_status (StatusID, [StatusName],[send_assessment],[order_num],[current_team],[IsActive]) VALUES
	(1,	'Active',	1,	1,	1, 1)
	,(2,	'Dormant',	0,	4,	0, 0)
	,(3,	'Disengaged',	0,	5,	0, 0)
	,(4,	'Active (Passive)',	0,	3,	1, 1)
	,(5,	'Not Part of Team',	0,	6,	0, 0)
	,(6,	'Observer',	1,	2,	1, 0)
	,(7,	'Active (Core Team)',	1,	0,	1, 1)
SET IDENTITY_INSERT dbo.user_status OFF





END

GO 

