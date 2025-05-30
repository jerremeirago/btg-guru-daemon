[x] - Implement GoalServe API
[x] - Refactor how API is being coded in the app, it should have an API Interface that would unify all API calls
[x] - Add API configuration 


Raw Data
```txt
for JSON output please add "?json=1" to the feed URL

--------------------------AFL AUSTRALIAN RULES-------------------



https://beatingthespreadguru.com/afl/matchcast_box/10457/12
    -> http://www.goalserve.com/getfeed/9645f122eef946c1c7bd08dd5ac0e712/afl/home - livescore
    -> http://www.goalserve.com/getfeed/9645f122eef946c1c7bd08dd5ac0e712/afl/schedule

http://www.goalserve.com/getfeed/9645f122eef946c1c7bd08dd5ac0e712/afl/standings

Old game stats by date attribute

https://www.goalserve.com/getfeed/9645f122eef946c1c7bd08dd5ac0e712/afl/home?date=19.06.2021

http://www.goalserve.com/getfeed/9645f122eef946c1c7bd08dd5ac0e712/afl/1019-stats - team stats
http://www.goalserve.com/getfeed/9645f122eef946c1c7bd08dd5ac0e712/afl/1019-rosters - team rosters

Pregame odds

https://www.goalserve.com/getfeed/9645f122eef946c1c7bd08dd5ac0e712/getodds/soccer?cat=afl_10
```


https://beatingthespreadguru.com/afl
https://beatingthespreadguru.com/afl/scores
https://beatingthespreadguru.com/afl/matchcast_box/10457/12