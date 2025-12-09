-- Query 1: Medal Count by Delegation
SELECT 
    d.Region AS RegionName,
    SUM(CASE WHEN p.Medal = 'Gold' THEN 1 ELSE 0 END) AS GoldCount,
    SUM(CASE WHEN p.Medal = 'Silver' THEN 1 ELSE 0 END) AS SilverCount,
    SUM(CASE WHEN p.Medal = 'Bronze' THEN 1 ELSE 0 END) AS BronzeCount,
    COUNT(p.Medal) AS TotalMedals
FROM Delegation d
LEFT JOIN Athlete a ON d.Delegation_id = a.DelegationID
LEFT JOIN Participation p ON a.Athlete_id = p.AthleteID AND p.Medal IS NOT NULL
GROUP BY d.Delegation_id, d.Region
ORDER BY TotalMedals DESC, GoldCount DESC, SilverCount DESC, BronzeCount DESC;

-- Query 2: Gold Medal Winners by Age Group
SELECT 
    CASE 
        WHEN a.Age BETWEEN 0 AND 18 THEN '0-18 years'
        WHEN a.Age BETWEEN 19 AND 30 THEN '19-30 years'
        WHEN a.Age BETWEEN 31 AND 45 THEN '31-45 years'
        ELSE '46+ years' 
    END AS AgeGroup,
    COUNT(*) AS GoldMedalCount,
    GROUP_CONCAT(a.Name) AS GoldMedalWinners
FROM Athlete a
JOIN Participation p ON a.Athlete_id = p.AthleteID
WHERE p.Medal = 'Gold'
GROUP BY AgeGroup
ORDER BY MIN(a.Age);

-- Query 3: Event Statistics
SELECT 
    e.EventName AS EventName,
    e.Level AS EventLevel,
    SUM(CASE WHEN p.Medal = 'Gold' THEN 1 ELSE 0 END) AS GoldCount,
    SUM(CASE WHEN p.Medal = 'Silver' THEN 1 ELSE 0 END) AS SilverCount,
    SUM(CASE WHEN p.Medal = 'Bronze' THEN 1 ELSE 0 END) AS BronzeCount,
    COUNT(*) AS TotalMedals
FROM Event e
JOIN Participation p ON e.EventID = p.EventID
WHERE p.Medal IS NOT NULL
GROUP BY e.EventID, e.EventName, e.Level
ORDER BY GoldCount DESC, SilverCount DESC, BronzeCount DESC;

-- Query 4: Category Manager Performance
SELECT 
    c.Manager AS ManagerName,
    c.Category_name AS CategoryName,
    COUNT(*) AS TotalMedals,
    SUM(CASE WHEN p.Medal = 'Gold' THEN 1 ELSE 0 END) AS GoldCount,
    SUM(CASE WHEN p.Medal = 'Silver' THEN 1 ELSE 0 END) AS SilverCount,
    SUM(CASE WHEN p.Medal = 'Bronze' THEN 1 ELSE 0 END) AS BronzeCount
FROM Category c
JOIN Event e ON c.Category_id = e.CategoryID
JOIN Participation p ON e.EventID = p.EventID
WHERE p.Medal IS NOT NULL AND c.Manager IS NOT NULL
GROUP BY c.Manager, c.Category_name
ORDER BY GoldCount DESC, SilverCount DESC, BronzeCount DESC;
