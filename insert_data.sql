INSERT INTO Delegation (Delegation_id, Region, Address) VALUES
('DEL01', 'Beijing', 'Beijing HQ'), ('DEL02', 'Tianjin', 'Tianjin HQ'),
('DEL03', 'Hebei', 'Shijiazhuang HQ'), ('DEL04', 'Shanxi', 'Taiyuan HQ'),
('DEL05', 'Inner Mongolia', 'Hohhot HQ'), ('DEL06', 'Liaoning', 'Shenyang HQ'),
('DEL07', 'Jilin', 'Changchun HQ'), ('DEL08', 'Heilongjiang', 'Harbin HQ'),
('DEL09', 'Shanghai', 'Shanghai HQ'), ('DEL10', 'Jiangsu', 'Nanjing HQ'),
('DEL11', 'Zhejiang', 'Hangzhou HQ'), ('DEL12', 'Anhui', 'Hefei HQ'),
('DEL13', 'Fujian', 'Fuzhou HQ'), ('DEL14', 'Jiangxi', 'Nanchang HQ'),
('DEL15', 'Shandong', 'Jinan HQ'), ('DEL16', 'Henan', 'Zhengzhou HQ'),
('DEL17', 'Hubei', 'Wuhan HQ'), ('DEL18', 'Hunan', 'Changsha HQ'),
('DEL19', 'Guangdong', 'Guangzhou HQ'), ('DEL20', 'Guangxi', 'Nanning HQ'),
('DEL21', 'Hainan', 'Haikou HQ'), ('DEL22', 'Chongqing', 'Chongqing HQ'),
('DEL23', 'Sichuan', 'Chengdu HQ'), ('DEL24', 'Guizhou', 'Guiyang HQ'),
('DEL25', 'Yunnan', 'Kunming HQ'), ('DEL26', 'Tibet', 'Lhasa HQ'),
('DEL27', 'Shaanxi', 'Xi''an HQ'), ('DEL28', 'Gansu', 'Lanzhou HQ'),
('DEL29', 'Qinghai', 'Xining HQ'), ('DEL30', 'Ningxia', 'Yinchuan HQ'),
('DEL31', 'Xinjiang', 'Urumqi HQ'), ('DEL32', 'Hong Kong', 'Hong Kong HQ'),
('DEL33', 'Macau', 'Macau HQ'), ('DEL34', 'Taiwan', 'Taipei HQ');


INSERT INTO Category (Category_id, Category_name, Manager) VALUES
('CAT01', 'Athletics', 'Manager A'), ('CAT02', 'Swimming', 'Manager B'),
('CAT03', 'Gymnastics', 'Manager C'), ('CAT04', 'Basketball', 'Manager D'),
('CAT05', 'Volleyball', 'Manager E'), ('CAT06', 'Table Tennis', 'Manager F'),
('CAT07', 'Badminton', 'Manager G'), ('CAT08', 'Football', 'Manager H'),
('CAT09', 'Shooting', 'Manager I'), ('CAT10', 'Archery', 'Manager J'),
('CAT11', 'Weightlifting', 'Manager K'), ('CAT12', 'Judo', 'Manager L'),
('CAT13', 'Wrestling', 'Manager M'), ('CAT14', 'Boxing', 'Manager N'),
('CAT15', 'Taekwondo', 'Manager O'), ('CAT16', 'Rowing', 'Manager P'),
('CAT17', 'Canoeing', 'Manager Q'), ('CAT18', 'Cycling', 'Manager R'),
('CAT19', 'Fencing', 'Manager S'), ('CAT20', 'Diving', 'Manager T');


INSERT INTO Event (EventID, CategoryID, EventName, Level)
WITH RECURSIVE cnt(x) AS (
    VALUES(1) UNION ALL SELECT x+1 FROM cnt WHERE x < 200
)
SELECT 
    printf('EVT%03d', x), 
    printf('CAT%02d', abs(random() % 20) + 1), 
    'Event Name ' || x,
    CASE (abs(random() % 3)) 
        WHEN 0 THEN 'Preliminaries'
        WHEN 1 THEN 'Semi-Final'
        ELSE 'Final'
    END
FROM cnt;

-- ==========================================
-- Generate Athletes (2500 rows) via CTE
-- ==========================================
INSERT INTO Athlete (Athlete_id, Name, Age, Gender, DelegationID)
WITH RECURSIVE cnt(x) AS (
    VALUES(1) UNION ALL SELECT x+1 FROM cnt WHERE x < 2500
)
SELECT 
    printf('ATH%04d', x),
    'Athlete ' || x,
    abs(random() % 25) + 15, 
    CASE WHEN random() > 0 THEN 'Male' ELSE 'Female' END,
    printf('DEL%02d', abs(random() % 34) + 1) 
FROM cnt;


INSERT OR IGNORE INTO Participation (AthleteID, EventID, Time, Medal)
WITH RECURSIVE cnt(x) AS (
    VALUES(1) UNION ALL SELECT x+1 FROM cnt WHERE x < 6500
)
SELECT 
    printf('ATH%04d', abs(random() % 2500) + 1),
    printf('EVT%03d', abs(random() % 200) + 1),
    datetime('2025-11-09 08:00:00', '+' || abs(random() % (12 * 24 * 3600)) || ' seconds'),
    CASE 
        WHEN abs(random() % 100) < 85 THEN NULL
        WHEN abs(random() % 100) < 90 THEN 'Gold'
        WHEN abs(random() % 100) < 95 THEN 'Silver'
        ELSE 'Bronze'
    END
FROM cnt;
