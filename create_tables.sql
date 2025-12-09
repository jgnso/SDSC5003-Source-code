DROP TABLE IF EXISTS Participation;
DROP TABLE IF EXISTS Athlete;
DROP TABLE IF EXISTS Event;
DROP TABLE IF EXISTS Category;
DROP TABLE IF EXISTS Delegation;


CREATE TABLE Delegation (
    Delegation_id VARCHAR(50) PRIMARY KEY,
    Region VARCHAR(50) NOT NULL UNIQUE,
    Address VARCHAR(100)
);

CREATE TABLE Category (
    Category_id VARCHAR(50) PRIMARY KEY,
    Category_name VARCHAR(50) NOT NULL UNIQUE,
    Manager VARCHAR(100)
);

CREATE TABLE Event (
    EventID VARCHAR(50) PRIMARY KEY,
    CategoryID VARCHAR(50) NOT NULL,
    EventName VARCHAR(100) NOT NULL,
    Level VARCHAR(50),
    FOREIGN KEY (CategoryID) REFERENCES Category(Category_id),
    UNIQUE (EventName, Level)
);

CREATE TABLE Athlete (
    Athlete_id VARCHAR(50) PRIMARY KEY,
    Name VARCHAR(50) NOT NULL,
    Age INT CHECK (Age >= 0 AND Age <= 120),
    Gender VARCHAR(6) CHECK (Gender IN ('Male', 'Female')),
    DelegationID VARCHAR(50) NOT NULL,
    FOREIGN KEY (DelegationID) REFERENCES Delegation(Delegation_id)
);

CREATE TABLE Participation (
    AthleteID VARCHAR(50),
    EventID VARCHAR(50),
    Time TIMESTAMP NOT NULL,
    Medal VARCHAR(10) CHECK (Medal IN ('Gold', 'Silver', 'Bronze') OR Medal IS NULL),
    PRIMARY KEY (AthleteID, EventID),
    FOREIGN KEY (AthleteID) REFERENCES Athlete(Athlete_id),
    FOREIGN KEY (EventID) REFERENCES Event(EventID)
);
