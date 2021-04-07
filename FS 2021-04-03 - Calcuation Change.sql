alter table payment_events
	drop column Valuation

alter table user_events_cache
	add valuation money,
		payout_amount money,
		percentage float

alter table user_payments
	add comment varchar(max)

alter table log_calc
	add Comment varchar(max)

EXEC sp_rename 'user_events_cache.Impact_money_risk_adjusted', 'Impact_money_w_risk', 'COLUMN';

create view_user_payments

alter table user_events_cache
	drop column Valuation 

alter table user_to_company
	add calc_status varchar(100)


Alter View_CompanyUsers

alter view_UserEventHistory


ALTER PROCEDURE [dbo].[ProcessPayment]

ALTER FUNCTION [dbo].[GetCompanyBreakdown]

ALTER PROCEDURE [dbo].[RegenerateCompanyData]

ALTER PROCEDURE [dbo].[UpdateUsersInCompany]


----  Removing money_cash

alter table user_events
	drop column money_cash

GO

ALTER PROCEDURE [dbo].[ProcessPayment]
	@UserPaymentID int
AS
BEGIN
	-- SET NOCOUNT ON added to prevent extra result sets from
	-- interfering with SELECT statements.
	SET NOCOUNT ON;

	-- Get transaction variables
	DECLARE @reinv float, @paid_amount float, @total_amount float, @uid int, @cid int, 	@pe_id int, @new_investment float, 	@reverse_investment float, @stake float

	select	@reinv = ReinvestAmount, 
			@paid_amount = PaidAmount, 
			@new_investment = new_invest_amount, 
			@reverse_investment = reverse_investment, 
			@total_amount = amount, 
			@uid = UserID, 
			@cid = CompanyID, 
			@pe_id = PaymentEventID,
			@stake = user_payments.Percentage -- Ownership on the reduced valuation
				from user_payments where ID = @UserPaymentID

	-- Get COntext variables
	DECLARE @valuation_temp float, 
			@investment float, 
			@event_date date

	select	@valuation_temp = ValuationTemp, 
			@event_date = payment_events.DatePay 
		from payment_events where ID = @pe_id
	
	select @investment = SUM(ISNULL( new_invest_amount, 0) + ISNULL( Reinvestamount, Amount) ) from user_payments where PaymentEventID = @pe_id

	if @reinv IS NULL OR @paid_amount IS NULL OR @reinv + @paid_amount <> @total_amount 
	BEGIN
		if @paid_amount IS NULL and @reinv IS NULL 
			SET @reinv = @total_amount

		if @reinv IS NULL and @paid_amount IS NOT NULL 
			SET @reinv = @total_amount - @paid_amount

		IF (@reinv + @paid_amount > @total_amount ) OR (@reinv = 0 AND @paid_amount = 0)
			SET @reinv = @total_amount

		SET @paid_amount = @total_amount - @reinv
	END
	
	update user_payments set ReinvestAmount = @reinv, PaidAmount = @paid_amount where ID = @UserPaymentID
	
	-- Let's add appropriate records into the events
	-- If there is a reinvestment, log it as a comment
	if @reinv > 0 
		INSERT into user_events (UserID, CompanyID, EventDate, Comment, money_note, PaymentEventID ) VALUES
			(@uid, @cid, @event_date, 'Payment Distribution / Reinvested: $' + CAST( @reinv as VARCHAR(20)), @reinv, @pe_id )

	
	-- If investment is happening without penalty (at current valuation), then just add it

	-- for both, the penalty (amount is amount / new valuation) * old valuation 
	-- SET @new_investment_full = @new_investment / (@valuation_temp + @investment) * @valuation
	-- SET @paid_amount_full	 = @paid_amount    / (@valuation_temp + @investment) * @valuation

	-- SET @new_investment_full = ISNULL( @new_investment_full, @new_investment )
	-- SET @paid_amount_full	 = ISNULL( @paid_amount_full, @paid_amount )


	if @new_investment > 0 
		INSERT into user_events (UserID, CompanyID, EventDate, Comment, money_transfer, PaymentEventID ) VALUES
			(@uid, @cid, @event_date, 'New Capital Investmentof $' + CAST( @new_investment AS VARCHAR(20)), @new_investment, @pe_id )
--			(@uid, @cid, @event_date, 'New Capital Investment of $' + CAST( @new_investment AS VARCHAR(20)) + ISNULL( ' at valuation of $' + CAST( @valuation_temp AS VARCHAR(20)), '' ), @new_investment_full, @pe_id, @new_investment )
	
	if @paid_amount > 0
		INSERT into user_events (UserID, CompanyID, EventDate, Comment, money_transfer, PaymentEventID ) VALUES
			(@uid, @cid, @event_date, 'Distribution Taken of $' + CAST( @paid_amount AS VARCHAR(20)), 0 - @paid_amount, @pe_id )
--			(@uid, @cid, @event_date, 'Distribution Taken of $' + CAST( @paid_amount AS VARCHAR(20)) + ISNULL( ' at valuation of $' + CAST( @valuation_temp AS VARCHAR(20)), '' ), 0 - @paid_amount_full, @pe_id, @paid_amount )

	Update user_payments set DatePaidOut = GETDATE() where ID = @UserPaymentID

END

GO


ALTER VIEW [dbo].[View_UserEventHistory]
AS
SELECT        e.EventID, CASE WHEN l.Name IS NOT NULL THEN '' + l.Name WHEN e.FLevelID IS NOT NULL THEN CASE WHEN FLevelID = - 2 THEN '---Throttle: No Change' ELSE '---Throttle: ' + f.Name END WHEN e.PLevelID IS NOT NULL 
                         AND e.PLevelID > 0 AND e.is_core = 1 THEN '-----' + p.Name + ' (Core)' WHEN e.PLevelID IS NOT NULL AND e.PLevelID > 0 THEN '-----' + p.Name WHEN e.LongCycleMultiplier IS NOT NULL 
                         THEN '-----Long Cycle:' + FORMAT(e.LongCycleMultiplier, 'N') WHEN Money_Transfer IS NOT NULL AND Impact_onetime IS NOT NULL THEN 'Impact / Cash' WHEN Money_Transfer IS NOT NULL AND valuation IS NULL 
                         THEN 'Investment: $' + Format(Money_Transfer, 'N') WHEN Money_Transfer IS NOT NULL AND valuation IS NOT NULL THEN 'Investment (w/ penalty): $' + Format(Money_Transfer, 'N') WHEN Impact_onetime IS NOT NULL 
                         THEN 'One-time Impact: $' + Format(Impact_onetime, 'N') ELSE 'Comment' END AS Event, e.UserID, e.CompanyID, e.EventDate, e.Comment, e.LongCycleMultiplier, CASE WHEN p.Name IS NOT NULL 
                         THEN p.Name ELSE '' END AS Evaluation, CASE WHEN p.Name IS NOT NULL AND e.FLevelID IS NOT NULL THEN p.Name ELSE '' END AS Throttle, l.Name AS [Level], l.[Level] AS LevelValue, e.T_to_saturation, l.LevelID, 
                         p.PLevelID, p.PLevel, p.Name AS PName, REPLACE(REPLACE(CASE WHEN LEN(e.Comment) > 50 THEN LEFT(e.Comment, 50) + '...' ELSE e.Comment END, CHAR(10), '<BR/>'), '  ', '&nbsp&nbsp') AS HTML_Comment_Limited, 
                         REPLACE(e.Comment, '  ', '&nbsp&nbsp') AS HTML_Comment, e.money_transfer, e.Impact_onetime, CASE WHEN l.Name IS NOT NULL THEN '#fff' WHEN e.FLevelID IS NOT NULL THEN '#FFE' WHEN e.PLevelID IS NOT NULL 
                         AND e.PLevelID > 0 THEN '#CEF' WHEN e.LongCycleMultiplier IS NOT NULL THEN '#CBF' WHEN Money_Transfer IS NOT NULL THEN '#D7D700' WHEN Impact_onetime IS NOT NULL 
                         THEN '#FFE5B4' ELSE '#EEE' END AS BG_Color, e.is_core, e.FLevelID, e.FilledOutLate, e.TimeSpent, dbo.user_events_cache.Level_Potential, dbo.user_events_cache.LevelGrowthPerHour, 
                         dbo.user_events_cache.LevelDecayPerDay, dbo.user_events_cache.RiskMultiplier, dbo.user_events_cache.LongCycleMultiplier AS Expr1, dbo.user_events_cache.[Level] AS Expr2, dbo.user_events_cache.PLevel_Backward, 
                         dbo.user_events_cache.Effective_LevelID, dbo.user_events_cache.T_to_saturation AS Expr3, dbo.user_events_cache.Impact_w_risk, dbo.user_events_cache.Impact_flat, dbo.user_events_cache.Impact_per_hour, 
                         dbo.user_events_cache.ImpactPerHour_start, dbo.user_events_cache.ImpactPerHour_end, dbo.user_events_cache.Impact_w_long_cycle, dbo.user_events_cache.Impact_w_long_cycle_risk, 
                         dbo.user_events_cache.Impact_money_flat, dbo.user_events_cache.Impact_money_w_risk, e.reset_potential, dbo.user_events_cache.Impact_Net_Labor, dbo.user_events_cache.Impact_Net_Capital, 
                         dbo.user_events_cache.Impact_Net_Onetime, dbo.users.FullName, e.PaymentEventID, dbo.user_events_cache.impact_onetime_w_risk, ISNULL(dbo.user_events_cache.Impact_w_long_cycle_risk, 0) 
                         + ISNULL(dbo.user_events_cache.Impact_money_w_risk, 0) + ISNULL(dbo.user_events_cache.impact_onetime_w_risk, 0) AS ImpactChange
FROM            dbo.users INNER JOIN
                         dbo.user_events_cache ON dbo.users.UserID = dbo.user_events_cache.UserID RIGHT OUTER JOIN
                         dbo.user_events AS e ON dbo.user_events_cache.EventID = e.EventID LEFT OUTER JOIN
                         dbo.user_levels AS l ON e.LevelID = l.LevelID LEFT OUTER JOIN
                         dbo.user_performance_levels AS p ON e.PLevelID = p.PLevelID LEFT OUTER JOIN
                         dbo.user_performance_levels AS f ON e.FLevelID = f.PLevelID

GO


/*
exec H_GenerateUserCache 3, 12, NULL

linear, setting data into user_events_cache without using segment-cache

*/

ALTER PROCEDURE [dbo].[ZH_GenerateUserCache_data]
	-- Add the parameters for the stored procedure here
	@user_id int,
	@company_id int
	--,@date_start datetime -- when @date_start is NULL, then generates only new data,
AS
BEGIN

		-- DECLARE @user_id INT = 3,
		--		@company_id INT = 10038

	-------------------------------------------
	-- Set Up the Cache -----------------------
	-------------------------------------------

	update user_to_company set calc_status = 'Started on ' + CAST( GETDATE() AS VARCHAR(MAX) ) where UserID = @user_id

	-- Remove all relevant cache so that we can rebuild it
	delete from user_events_cache where UserID = @user_id AND CompanyID = @company_id

	-- add relevant events back in: Level events, Short-cycle Performance appraisals, long-cycle appraisals
	insert into user_events_cache (
			EventID, UserID, CompanyID, EventDate, 
			PLevel_Backward, PLevel_BackwardID, 
			impact_onetime_flat, 
			Impact_money_flat, 
			decay_potential, valuation, payout_amount, percentage )
		Select 
			EventID, 
			@user_id,
			@company_id,
			EventDate,
			upl.PLevel,
			upl.PLevelID,   -- impact through work
			Impact_onetime, -- recognition of impact that is not cash
			money_transfer, -- transfer of money
			decay_potential,
			up.ValuationTemp, -- The pre-money valuation is temporary valuation + amount paid out
			up.Amount, -- the amount of payout
			upi.Percentage -- Percentage ownership
		from user_events ue
			left join user_performance_levels upl on upl.PLevelID = ue.PLevelID
			left join user_levels ul on ul.LevelID = ue.LevelID
			left join payment_events up on up.ID = ue.PaymentEventID
			left join user_payments upi on upi.PaymentEventID = up.ID and upi.UserID = ue.UserID
			WHERE 
				ue.UserID = @user_id AND ue.CompanyID = @company_id AND 
				(ue.PLevelID > 0 OR ue.LevelID > 0 OR ue.LongCycleMultiplier IS NOT NULL OR ue.money_transfer IS NOT NULL OR ue.Impact_onetime IS NOT NULL  )

	-- Set levels 
	update user_events_cache
		set 
			Level = ul.Level,
			LevelID = ul.LevelID,
			T_to_saturation = l.T_to_saturation
		from user_events_cache e  -- This can be done for all companies very fast
		inner join 
			(select 
			EventDate as EventDate1, LEAD(EventDate) OVER( PARTITION BY UserID, CompanyID ORDER BY EventDate ASC ) as NextDate, *  
			from user_events where LevelID IS NOT NULL and LevelID > 0 ) l
			on e.UserID = l.UserID and e.CompanyID = l.CompanyID
				and l.EventDate <= e.EventDate
				and (e.EventDate < l.NextDate or l.Nextdate IS NULL)
				and Impact_money_flat IS NULL
				and Impact_onetime_flat IS NULL
		left join user_levels ul on ul.LevelID = l.LevelID
		where e.UserID = @user_id and e.CompanyID = @company_id
		--order by e.UserID, e.CompanyID, e.EventDate

	-- set LongCycleMultiplier
	update user_events_cache
		set LongCycleMultiplier = ISNULL( l.LongCycleMultiplier, 1 ) -- Default Long-Cycle Multiplier
		from user_events_cache e  -- This can be done for all companies very fast
		inner join 
			(select 
			EventDate as EventDate1, LEAD(EventDate) OVER( PARTITION BY UserID, CompanyID ORDER BY EventDate ASC ) as NextDate, *  
			from user_events where LongCycleMultiplier IS NOT NULL ) l
			on e.UserID = l.UserID and e.CompanyID = l.CompanyID
				and e.EventDate <= l.EventDate
				and (e.EventDate < l.NextDate or l.Nextdate IS NULL)
		where e.UserID = @user_id and e.CompanyID = @company_id

	-- set Risk Multiplier
	update user_events_cache
		set RiskMultiplier = r.Multiplier
		from user_events_cache c
			inner join
			(select 
					LEAD(Date) OVER( PARTITION BY CompanyID ORDER BY Date ASC ) AS Date_Next, 
					* from risk_multipliers where CompanyID = @company_id) r
			on r.date <= c.EventDate and (c.EventDate < r.Date_Next or r.Date_Next IS NULL)
		where c.UserID = @user_id and c.CompanyID = @company_id

	-- Update what we can set-based
	update user_events_cache 
		set 
			--LevelGrowthPerHour = Level / (T_to_saturation * 8),
			--LevelDecayPerDay = -1 * Level * (1 - ISNULL( PLevel_Backward, 1 ) ) / (T_to_saturation),
			Impact_money_w_risk		   = Impact_money_flat * ISNULL( RiskMultiplier, 1),
			Impact_onetime_w_risk	   = Impact_onetime_flat * ISNULL( RiskMultiplier, 1)
			where UserID = @user_id and CompanyID = @company_id
				
	-------------------------------------------
	-- Do Sequestial Calculations -------------
	-------------------------------------------

	-- Set up Aggregatation Variables
	DECLARE	 @Impact_Net_Labor			money = 0 
			,@Impact_Net_Labor_NoRisk	money = 0
			,@Impact_Net_Onetime		money = 0			
			,@Impact_Net_Onetime_NoRisk	money = 0 
			,@Impact_Net_Capital		money = 0			 
			,@Impact_Net_Capital_NoRisk	money = 0 

	-- Set up our cursor to iterate
/*
	DECLARE potential_cursor CURSOR
	FOR select 
		c.EventID,
		Level, 
		c.EventDate,
		dbo.GetAt_T_to_Saturation( @user_id, @company_id, c.EventDate),  --.T_to_saturation, 
		Level_Potential, 
		ISNULL( c.PLevel_Backward, 1), 
		ISNULL( is_core,  0 ), 
		ISNULL( FilledOutLate, 0 ) as is_late, 
		ISNULL( TimeSpent, 0 ), 
		ISNULL (c.LongCycleMultiplier, 1 ),
		reset_potential,
		decay_potential,
		valuation
		 from user_events_cache c
			inner join user_events e on e.EventID = c.EventID
			where c.UserID = @user_id and c.CompanyID = @company_id order by c.EventDate ASC, c.EventID ASC

	OPEN potential_cursor
*/	
	
	DECLARE @Impact_per_hour_start float = NULL, 
			@Impact_per_hour_end float = NULL,

			@EventID int, 
			@Level float, 
			@EventDate date,
			@T_to_saturation float, 
			@PotentialChange float, 
			@Level_Potential float, 
			@PLevel_Backward float, 
			@is_core bit, 
			@is_late bit, 
			@TimeSpent float,
			@LongCycleMultiplier float,
			@reset_potential bit, -- when true, the potential is reset on this date as if a person just started
			@decay_potential bit, -- when true, the potential decays if it's zero
			@valuation money,
			@payout_amount money

	DECLARE @event_table TABLE (
		EventID bigint,
		EventDate datetime
	)

	-- get all cycles to process
	INSERT INTO @event_table 
		select EventID, EventDate from user_events_cache where UserID = @user_id and CompanyID = @company_id

	-- Get first record
	SELECT top 1 @EventID = EventID from @event_table ORDER BY EventDate ASC, EventID DESC

	-- Get the Starting Position
	SELECT 
		@Level = 							Level, 
		@EventDate = 						c.EventDate,
		@T_to_saturation = 					dbo.GetAt_T_to_Saturation( @user_id, @company_id, c.EventDate),  --.T_to_saturation, 
		@Level_Potential = 					Level_Potential, 
		@PLevel_Backward = 					ISNULL( PLevel_Backward, 1), 
		@is_core = 							ISNULL( is_core,  0 ), 
		@is_late = 							ISNULL( FilledOutLate, 0 ), 
		@TimeSpent = 						ISNULL( TimeSpent, 0 ), 
		@LongCycleMultiplier = 				ISNULL (c.LongCycleMultiplier, 1 ),
		@reset_potential = 					reset_potential,
		@decay_potential = 					decay_potential,
		@valuation =						valuation,  -- temporary valuation
		@payout_amount =					ISNULL( payout_amount, 0 )
		FROM user_events_cache c 
			inner join user_events e on e.EventID = c.EventID
			where c.EventID = @EventID
	

	
	/*FETCH NEXT FROM potential_cursor
		INTO 
			@EventID,							
			@Level, 							
			@EventDate, 						
			@T_to_saturation, 					
			@Level_Potential, 					
			@PLevel_Backward, 					
			@is_core, 							
			@is_late, 							
			@TimeSpent, 						
			@LongCycleMultiplier, 				
			@reset_potential, 					
			@decay_potential, 					
			@valuation;
			*/
	
	DECLARE @L_goal			float = 0,
			@impact_flat	float = 0,
			@Impact_per_hour float = 0,
			@cur_L_p		float = @Level,  -- start at the level where we started
			@NumDays		int,

			@Date_last		date,
			@impact_money_at_valuation money

--	WHILE @@FETCH_STATUS = 0
	WHILE @EventID IS NOT NULL
	BEGIN

		SET @Date_last = @EventDate  

		-- Reset potential?
		IF	@reset_potential = 1 OR 
			( @cur_L_p IS NULL and @Level IS NOT NULL) -- in case it was not yet set
				SET @cur_L_p = @Level

		-- Update the last record that we were on before moving next
		update user_events_cache
			SET 
				Level_Potential				= @cur_L_p
				,Impact_per_hour			= @Impact_per_hour
				,Impact_flat				= @impact_flat
				,Impact_w_risk				= @impact_flat * ISNULL( RiskMultiplier, 1 )
				,Impact_w_long_cycle		= @impact_flat * @LongCycleMultiplier
				,Impact_w_long_cycle_risk	= @impact_flat * @LongCycleMultiplier * ISNULL( RiskMultiplier, 1 )
				,ImpactPerHour_start		= @Impact_per_hour_start
				,ImpactPerHour_end			= @Impact_per_hour_end
				-- Done above as a table
				-- ,Impact_money_w_risk		= Impact_money_flat		* ISNULL( RiskMultiplier, 1 )
				-- ,Impact_onetime_w_risk		= Impact_onetime_flat	* ISNULL( RiskMultiplier, 1 )
			where EventID = @EventID
			--WHERE CURRENT OF potential_cursor

		-- adjustment of money, if it's no a valuation
		if @valuation > 0 
		BEGIN

			SELECT * from user_events_cache Where EventID = @EventID

			update user_events_cache
				SET Impact_money_w_risk = 
						(@Impact_Net_Labor + @Impact_Net_Onetime + @Impact_Net_Capital) / ( percentage / 100 ) * 
						impact_money_flat / (@valuation + @payout_amount)
				where EventID = @EventID

			SELECT @impact_money_at_valuation = Impact_money_w_risk
				from user_events_cache
				Where EventID = @EventID
			
			update user_events 
				set Comment = CONCAT( 'Payout of $', FORMAT( money_transfer, 'N' ), 
									  ' at pre-money valuation of $',  FORMAT( @valuation, 'N' ), 
									  ' with total amount paid out at $',  FORMAT( @payout_amount, 'N' ), 
									  ' resulting in change of $', FORMAT( @impact_money_at_valuation , 'N' ),
									  '<br/>',
									  '<hr/>',
									  '<br/>',
									  '(Current Total Impact) / (percentage), gives us Valuation, which we multiply by the amount of money / (Pre-money valuation + payout amount)',
									  '<br/>',
									  '(', @Impact_Net_Labor,' + ', @Impact_Net_Onetime,' + ', @Impact_Net_Capital, ') / ( ', c.percentage, ' / 100 ) * ',
									  c.impact_money_flat, ' / (', @valuation,' + ', @payout_amount,')' --- it may be better to use amount reinvested rather than payout amount
									  )
			from user_events 
				inner join user_events_cache c on c.EventID = user_events.EventID
			Where user_events.EventID = @EventID
		END


		-- Update Aggregates
		SELECT 
		 @Impact_Net_Labor			= @Impact_Net_Labor			 + ISNULL( Impact_w_long_cycle_risk, 0 )
		,@Impact_Net_Labor_NoRisk	= @Impact_Net_Labor_NoRisk	 + ISNULL( Impact_w_long_cycle, 0 )

		,@Impact_Net_Onetime		= @Impact_Net_Onetime		 + ISNULL( impact_onetime_flat, 0 )
		,@Impact_Net_Onetime_NoRisk	= @Impact_Net_Onetime_NoRisk + ISNULL( impact_onetime_w_risk, 0 )

		,@Impact_Net_Capital		= @Impact_Net_Capital		 + ISNULL( Impact_money_w_risk, 0 )
		,@Impact_Net_Capital_NoRisk	= @Impact_Net_Capital_NoRisk + ISNULL( impact_money_flat, 0 )

			FROM user_events_cache
			WHERE EventID = @EventID
		
		update user_events_cache SET
				 Impact_Net_Labor			= @Impact_Net_Labor			
				,Impact_Net_Labor_NoRisk	= @Impact_Net_Labor_NoRisk	
				,Impact_Net_Onetime			= @Impact_Net_Onetime		
				,Impact_Net_Onetime_NoRisk	= @Impact_Net_Onetime_NoRisk
				,Impact_Net_Capital			= @Impact_Net_Capital		
				,Impact_Net_Capital_NoRisk	= @Impact_Net_Capital_NoRisk
			where EventID = @EventID


		-- Calculate Potential and Impact from Labor
		-- Reset impact after loading
		SET @impact_flat = 0
		
		-- Lets get the next item to work with
/*		FETCH NEXT FROM potential_cursor
		INTO 
			@EventID, @Level, @EventDate, @T_to_saturation, @Level_Potential, @PLevel_Backward, @is_core, @is_late, @TimeSpent, @LongCycleMultiplier, @reset_potential, @decay_potential, @valuation;
*/
		-- GET THE NEXT RECORD TO WORK WITH
		DELETE from @event_table where EventID = @EventID
		SET @EventID = NULL
		SELECT top 1 @EventID = EventID from @event_table ORDER BY EventDate ASC, EventID DESC

		SELECT 
		@Level = 							Level, 
		@EventDate = 						c.EventDate,
		@T_to_saturation = 					dbo.GetAt_T_to_Saturation( @user_id, @company_id, c.EventDate),  --.T_to_saturation, 
		@Level_Potential = 					Level_Potential, 
		@PLevel_Backward = 					ISNULL( PLevel_Backward, 1), 
		@is_core = 							ISNULL( is_core,  0 ), 
		@is_late = 							ISNULL( FilledOutLate, 0 ), 
		@TimeSpent = 						ISNULL( TimeSpent, 0 ), 
		@LongCycleMultiplier = 				ISNULL (c.LongCycleMultiplier, 1 ),
		@reset_potential = 					reset_potential,
		@decay_potential = 					decay_potential,
		@valuation =						valuation,  -- temporary valuation
		@payout_amount =					ISNULL( payout_amount, 0 )
		FROM user_events_cache c 
			inner join user_events e on e.EventID = c.EventID
			where c.EventID = @EventID	


		SET @NumDays = DATEDIFF( "D", @Date_last, @EventDate )

		DECLARE @L_base float
		SET @L_base = @Level
		if @is_core = 1 SET @L_base = @L_base * 1.25
 
		SET @L_goal = @L_base * @PLevel_Backward
		if @is_late = 1 SET @L_goal = @L_goal * 0.9

		Set @PotentialChange = @L_base * 0.025  --  BASE change is 2.5% of base level

		DECLARE @dir int = 0 , @new_dir int = 0

		IF (@L_goal - @cur_L_p) <> 0 
			SET @dir = (@L_goal - @cur_L_p) / ABS( @L_goal - @cur_L_p)
	
		-- Check if there is a need to adjust
		IF (@L_goal <> @cur_L_p)
		BEGIN

			-- If we are at expectation, restore towards base level
			if @PLevel_Backward = 1 
			BEGIN
				Set @PotentialChange = @PotentialChange / 1.5 -- we grow back at slightly slower than we grow
				SET @cur_L_p = @cur_L_p + @PotentialChange * @dir
			END

			-- If there is a performance adjustment or we should decay to 0
			ELSE IF (@L_goal > 0 OR @decay_potential = 1)
			BEGIN
				Set @PotentialChange = @PotentialChange * 
						(abs(@PLevel_Backward - 1) / 0.25) -- scale potential to level of performance
				SET @cur_L_p = @cur_L_p + @PotentialChange * @dir
			END


			-- check if we have changed direction and overshot our goal level
			SET @new_dir = 0
			IF (@L_goal - @cur_L_p) <> 0 
				SET @new_dir = (@L_goal - @cur_L_p) / ABS( @L_goal - @cur_L_p)

			
			if @new_dir <> @dir
				SET @cur_L_p = @L_goal
		END	
				
		--- Impact Calculation

		-- if @L_goal is 0, that means no work was done.  So there should be no changes to start and end
		if @L_goal > 0 
		BEGIN
			SET @Impact_per_hour_start = @Impact_per_hour_end
			SET @Impact_per_hour_end = @cur_L_p / .208 * @PLevel_Backward  -- $10,000 per level / 52 weeks / 40 hours per week = .208 
		
			if @Impact_per_hour_start IS NULL  -- 
				SET @Impact_per_hour_start = @Impact_per_hour_end
		
			SET @Impact_per_hour = (@Impact_per_hour_start + @Impact_per_hour_end) / 2

			if @is_core = 1 SET @Impact_per_hour = @Impact_per_hour * 1.25
			if @is_late = 1 SET @Impact_per_hour = @Impact_per_hour * 0.9

			--PRINT 'TIME: ' + CAST( @TimeSpent AS VARCHAR(10) )
			SET @impact_flat = @Impact_per_hour * ISNULL( @TimeSpent, 0 ) 
			--PRINT 'Impact: ' + CAST( @impact_flat AS VARCHAR(10) )
		END


	END

/*	CLOSE potential_cursor  
	DEALLOCATE potential_cursor  */

	update user_to_company set calc_status = 'OK' where UserID = @user_id

	-- Added above into sequential operation
	/*
	update user_events_cache
	SET Impact_money_w_risk = Impact_money_flat * ISNULL( RiskMultiplier, 1 ),
		Impact_onetime_w_risk	   = Impact_onetime_flat * ISNULL( RiskMultiplier, 1 )
	where UserID = @user_id and CompanyID = @company_id
	*/

	-- Moved into sequential, since percentage adjustment will influence this
	/*
	DECLARE @t_sum TABLE
	(
		EventID bigint,
		EventDate date,
		Impact_Net_Labor float,
		Impact_Net_Onetime float,
		Impact_Net_Capital float,
		Impact_Net_Labor_NoRisk float,
		Impact_Net_Onetime_NoRisk float,
		Impact_Net_Capital_NoRisk float
	)

	DECLARE @temp_t_work TABLE
	(
		EventID bigint,
		EventDate date,
		Impact_flat float,
		Impact_w_long_cycle_risk float,
		Impact_Onetime_flat float,
		Impact_Onetime_Risk_Adjusted float,
		Impact_Money float,
		Impact_money_w_risk float
	)

	insert into @temp_t_work 
		select EventID, EventDate, Impact_flat, Impact_w_long_cycle_risk, Impact_Onetime_flat, Impact_onetime_w_risk, Impact_Money_Flat, Impact_money_w_risk
				from user_events_cache 
				where UserID = @user_id and CompanyID = @company_id
	
	insert into @t_sum (EventID, EventDate, Impact_Net_Labor_NoRisk, Impact_Net_Labor, Impact_Net_Onetime_NoRisk, Impact_Net_Onetime, Impact_Net_Capital_NoRisk, Impact_Net_Capital  ) 
		(select EventID, EventDate

			-- Impact_Net_Labor_NoRisk
			, sum( ISNULL( Impact_flat, 0 ) ) over ( 
				 --partition by UserID
				order by EventDate, EventID DESC rows unbounded preceding) as Impact_Net_Labor_NoRisk

			-- Impact_Net_Labor
			, sum( ISNULL( Impact_w_long_cycle_risk, 0 ) ) over (
				 --partition by UserID
				order by EventDate, EventID DESC rows unbounded preceding) as Impact_Net_Labor

			-- Impact_Net_Onetime_NoRisk
			, sum( ISNULL( Impact_Onetime_flat, 0 ) ) over (
				 --partition by UserID
				order by EventDate, EventID DESC rows unbounded preceding) as Impact_Net_Onetime_NoRisk

			-- Impact_Net_Onetime
			, sum( ISNULL( Impact_Onetime_Risk_Adjusted, 0 ) ) over (
				 --partition by UserID
				order by EventDate, EventID DESC rows unbounded preceding) as Impact_Net_Onetime

			-- Impact_Net_Capital_NoRisk
			, sum( ISNULL( Impact_Money, 0 ) ) over (
				 --partition by UserID
				order by EventDate, EventID DESC rows unbounded preceding) as Impact_Net_Capital_NoRisk

			-- Impact_Net_Capital
			, sum( ISNULL( Impact_money_w_risk, 0 ) ) over (
				 --partition by UserID
				order by EventDate, EventID DESC rows unbounded preceding) as Impact_Net_Capital

					from @temp_t_work )

	-- Update totals				
		
	;with cteRowNumber as (
		select EventDate, EventID
		,Impact_Net_Labor			
		,Impact_Net_Labor_NoRisk		
		,Impact_Net_Onetime			
		,Impact_Net_Onetime_NoRisk	
		,Impact_Net_Capital			
		,Impact_Net_Capital_NoRisk	
		, row_number() over(partition by EventDate order by EventDate asc, EventID asc) as RowNum
			from @t_sum
	)
	update user_events_cache
		set 
			Impact_Net_Labor			= s.Impact_Net_Labor,
			Impact_Net_Labor_NoRisk		= s.Impact_Net_Labor_NoRisk,
			Impact_Net_Onetime			= s.Impact_Net_Onetime,
			Impact_Net_Onetime_NoRisk	= s.Impact_Net_Onetime_NoRisk,
			Impact_Net_Capital			= s.Impact_Net_Capital,
			Impact_Net_Capital_NoRisk	= s.Impact_Net_Capital_NoRisk
			from user_events_cache t inner join 
				(
					select 
							EventDate
							,Impact_Net_Labor			
							,Impact_Net_Labor_NoRisk		
							,Impact_Net_Onetime			
							,Impact_Net_Onetime_NoRisk	
							,Impact_Net_Capital			
							,Impact_Net_Capital_NoRisk	
							from cteRowNumber
							where RowNum = 1
				) s on t.EventDate = s.EventDate and t.UserID = @user_id and t.CompanyID = @company_id
	*/
			
	SET NOCOUNT ON;

	END_EXECUTION:
END


GO

