
CREATE INDEX PK_Delegation ON Delegation(Delegation_id);

CREATE INDEX PK_Athlete ON Athlete(Athlete_id);
CREATE INDEX FK_Delegation_Athlete ON Athlete(DelegationID);
CREATE INDEX idx_athlete_search ON Athlete(Name);


CREATE INDEX PK_Category ON Category(Category_id);

CREATE INDEX PK_Event ON Event(EventID);
CREATE INDEX FK_Event_Category ON Event(CategoryID);


CREATE INDEX PK_Participation ON Participation(AthleteID, EventID);
CREATE INDEX FK_Participation_Athlete ON Participation(AthleteID);
CREATE INDEX FK_Participation_Event ON Participation(EventID);
CREATE INDEX idx_event_medal ON Participation(EventID, Medal, Time);
