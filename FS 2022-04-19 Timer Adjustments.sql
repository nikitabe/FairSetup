
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION Timer_GetRange
(
	@time_start datetime,
	@time_end datetime,
	@time_offset int
)
RETURNS VARCHAR( 100 )
AS
BEGIN

	SET @time_start = DateAdd("HH", @time_offset, @time_start )
	SET @time_end   = DateAdd("HH", @time_offset, @time_end)
	RETURN FORMAT( @time_start, N'hh:mm tt' ) + ' - ' + ISNULL( FORMAT( @time_end, N'hh:mm tt' ), 'In Progress')

END
GO

GO

ALTER table users
	add [TimeOffset] [int] DEFAULT ((0))

GO

update users Set TimeOffset = 0

