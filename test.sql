select user_id from ( select user_id , count(*) c from user_fans group by user_id order by c desc limit 0,5) as fans_count;

select count(*),avg('monthsalary') from coutomers c union salary s on c.id=s.id where c.gender=M;

-- 建立索引
alter table user add name_age(name,age);

select count(*),avg('monthsalary') from coustomers1 where gender=0;
alter table user add index gender(gender);
grant all on *.* to 'root'@'192.168.203.20' identified by 'repl';


select elt(
interval(score,0,50,60,70,80,90),
'<50','50-60','60-70','70-80','80-90','90-100'
) as score_level ,count(*) as counts
from class
group by elt(
interval(score,0,50,60,70,80,90),
'<50','50-60','60-70','70-80','80-90','90-100'
);

SELECT elt(
  INTERVAL(score, 0, 50, 60, 70, 80, 90, 100),
  '<50', '50-60', '60-70', '70-80', '80-90', '90-100', '>=100'
) as score_level,count(*) as counts
FROM class
GROUP BY elt(
  INTERVAL(score, 0, 50, 60, 70, 80, 90, 100),
  '<50', '50-60', '60-70', '70-80', '80-90', '90-100', '>=100'
);