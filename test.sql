select user_id from ( select user_id , count(*) c from user_fans group by user_id order by c desc limit 0,5) as fans_count;

select count(*),avg('monthsalary') from coutomers c union salary s on c.id=s.id where c.gender=M;