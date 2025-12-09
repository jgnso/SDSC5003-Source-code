-- View 1: Event Information Overview
CREATE VIEW IF NOT EXISTS Event_Info_View AS
SELECT 
    e.EventID,
    e.EventName,
    e.Level AS Event_Level,
    c.Category_id,
    c.Category_name,
    c.Manager AS Category_Manager,
    p.Time AS Competition_Time,
    p.Medal,
    a.Athlete_id,
    a.Name AS Athlete_Name,
    a.Age,
    a.Gender,
    d.Delegation_id,
    d.Region AS Delegation_Region
FROM Event e
INNER JOIN Category c ON e.CategoryID = c.Category_id
LEFT JOIN Participation p ON e.EventID = p.EventID
LEFT JOIN Athlete a ON p.AthleteID = a.Athlete_id
LEFT JOIN Delegation d ON a.DelegationID = d.Delegation_id;

-- View 2: Athlete Information Overview
CREATE VIEW IF NOT EXISTS Athlete_Info_View AS
SELECT 
    a.Athlete_id,
    a.Name AS Athlete_Name,
    a.Age,
    a.Gender,
    d.Delegation_id,
    d.Region AS Delegation_Region,
    d.Address AS Delegation_Address,
    e.EventName,
    e.Level AS Event_Level,
    p.Time AS Competition_Time,
    p.Medal,
    c.Category_name
FROM Athlete a
LEFT JOIN Delegation d ON a.DelegationID = d.Delegation_id
LEFT JOIN Participation p ON a.Athlete_id = p.AthleteID
LEFT JOIN Event e ON p.EventID = e.EventID
LEFT JOIN Category c ON e.CategoryID = c.Category_id;
