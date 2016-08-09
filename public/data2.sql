Insert into logs
select null, from_ip, to_24_subnet, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, to_8_subnet, to_16_subnet, to_24_subnet, SUM(hits), null, 1, SUM(weight), 1
from logs
where parent IS null
group by
from_ip, to_24_subnet, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, to_8_subnet, to_16_subnet, to_24_subnet
having count(from_ip)>1;
UPDATE logs l
INNER JOIN logs l1 ON
l.from_ip = l1.from_ip
AND l.port = l1.port
and l.protocol = l1.protocol
and l.to_24_subnet = l1.to_ip
and l.to_ip != l1.to_ip
and l.parent is null
SET l.parent = l1.id;
Insert into logs select null, from_ip, to_16_subnet, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, to_8_subnet, to_16_subnet, 0, SUM(hits), null, 2, SUM(weight), 2
 from logs
where parent IS null
group by
from_ip, to_16_subnet, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, to_8_subnet, to_16_subnet
having count(from_ip)>1;
UPDATE logs l
INNER JOIN logs l1 ON
l.from_ip = l1.from_ip
AND l.port = l1.port
and l.protocol = l1.protocol
and l.to_16_subnet = l1.to_ip
and l.to_ip != l1.to_ip
and l.parent is null
SET l.parent = l1.id;
Insert into logs select null, from_ip, to_8_subnet, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, to_8_subnet, 0, 0, SUM(hits), null, 3, SUM(weight), 3
 from logs
where parent IS null
group by
from_ip, to_8_subnet, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, to_8_subnet
having count(from_ip)>1;
UPDATE logs l
INNER JOIN logs l1 ON
l.from_ip = l1.from_ip
AND l.port = l1.port
and l.protocol = l1.protocol
and l.to_8_subnet = l1.to_ip
and l.to_ip != l1.to_ip
and l.parent is null
SET l.parent = l1.id;
Insert into logs select null, from_ip, 0, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, 0, 0, 0, SUM(hits), null, 4, SUM(weight), 4
 from logs
where parent IS null
group by
from_ip, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet
having count(from_ip)>1;
UPDATE logs l
INNER JOIN logs l1 ON
l.from_ip = l1.from_ip
and l.protocol = l1.protocol
AND l.port = l1.port
and l.to_8_subnet != 0
and l.to_ip != 0
and l.to_ip != l1.to_ip
and l1.to_ip = 0
and l.parent is null
SET l.parent = l1.id;
Insert into logs select null, from_24_subnet, 0, port, protocol, from_8_subnet, from_16_subnet, from_24_subnet, 0, 0, 0, SUM(hits), null, 5, SUM(weight),5
 from logs
where parent IS null
group by
port, protocol, from_8_subnet, from_16_subnet, from_24_subnet
having count(from_ip)>1;
UPDATE logs l, (SELECT ID, from_ip, to_ip, port, protocol
                        FROM logs
                       WHERE to_ip = 0 AND parent IS null AND from_ip = from_24_subnet) l1
   SET l.parent = l1.id
 WHERE l.port = l1.port
and l.protocol = l1.protocol
and l.from_24_subnet = l1.from_ip
and l.from_ip != l1.from_ip
and l.parent is null;
Insert into logs select null, from_16_subnet, 0, port, protocol, from_8_subnet, from_16_subnet, 0, 0, 0, 0, SUM(hits), null, 6, SUM(weight),6
 from logs
where parent IS null
group by
port, protocol, from_8_subnet, from_16_subnet
having count(from_ip)>1;
UPDATE logs l, (SELECT ID, from_ip, to_ip, port, protocol
                        FROM logs
                       WHERE to_ip = 0 AND parent IS null AND from_ip = from_16_subnet) l1
   SET l.parent = l1.id
 WHERE l.port = l1.port
and l.protocol = l1.protocol
and l.from_16_subnet = l1.from_ip
and l.from_ip != l1.from_ip
and l.parent is null;
Insert into logs select null, from_8_subnet, 0, port, protocol, from_8_subnet, 0, 0, 0, 0, 0, SUM(hits), null, 7, SUM(weight), 7
 from logs
where parent IS null
group by
port, protocol, from_8_subnet
having count(from_ip)>1;
UPDATE logs l, (SELECT ID, from_ip, to_ip, port, protocol
                        FROM logs
                       WHERE to_ip = 0 AND parent IS null AND from_ip = from_8_subnet) l1
   SET l.parent = l1.id
 WHERE l.port = l1.port
and l.protocol = l1.protocol
and l.from_8_subnet = l1.from_ip
and l.from_ip != l1.from_ip
and l.parent is null;
Insert into logs select null, 0, 0, port, protocol, 0, 0, 0, 0, 0, 0, SUM(hits), null, 8, SUM(weight), 8
 from logs
where parent IS null
group by
port, protocol
having count(from_ip)>1;
UPDATE logs l, (SELECT ID, from_ip, to_ip, port, protocol
                        FROM logs
                       WHERE to_ip = 0 AND parent IS null AND from_ip = 0 AND port !=0) l1
   SET l.parent = l1.id
 WHERE l.port = l1.port
and l.protocol = l1.protocol
and l.from_ip != 0
and l.from_ip != l1.from_ip
and l.parent is null;
Insert into logs select null, 0, 0, 0, protocol, 0, 0, 0, 0, 0, 0, SUM(hits), null, 10, SUM(weight), 10
 from logs
where parent IS null
group by
protocol
having count(from_ip)>1;
UPDATE logs l, (SELECT ID, protocol
                        FROM logs
                       WHERE parent IS null and tolerance=10) l1
   SET l.parent = l1.id
 WHERE l.protocol = l1.protocol
and l.parent is null
and l.tolerance<10;
