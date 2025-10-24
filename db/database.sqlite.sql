BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "booked_seats" (
	"id"	INTEGER,
	"ticket_id"	INTEGER NOT NULL,
	"seat_number"	INTEGER NOT NULL,
	"created_at"	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id"),
	UNIQUE("ticket_id","seat_number"),
	FOREIGN KEY("ticket_id") REFERENCES "tickets"("id")
);
CREATE TABLE IF NOT EXISTS "cities" (
	"id"	INTEGER,
	"name"	TEXT NOT NULL UNIQUE,
	"created_at"	TEXT DEFAULT (datetime('now')),
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "companies" (
	"id"	INTEGER,
	"name"	TEXT NOT NULL UNIQUE,
	"logo_path"	TEXT,
	"created_at"	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "coupons" (
	"id"	INTEGER,
	"code"	TEXT NOT NULL UNIQUE,
	"discount_rate"	REAL NOT NULL,
	"usage_limit"	INTEGER NOT NULL,
	"used_count"	INTEGER DEFAULT 0,
	"expiry_date"	DATETIME NOT NULL,
	"company_id"	INTEGER,
	"created_at"	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	"status"	TEXT DEFAULT 'active',
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("company_id") REFERENCES "companies"("id")
);
CREATE TABLE IF NOT EXISTS "tickets" (
	"id"	INTEGER,
	"trip_id"	INTEGER NOT NULL,
	"user_id"	INTEGER NOT NULL,
	"total_price"	REAL NOT NULL,
	"status"	TEXT DEFAULT 'active' CHECK("status" IN ('active', 'canceled', 'expired')),
	"created_at"	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	"final_price"	INT,
	"coupon_id"	INTEGER,
	"coupon_code"	TEXT,
	PRIMARY KEY("id"),
	FOREIGN KEY("coupon_id") REFERENCES "coupons"("id"),
	FOREIGN KEY("trip_id") REFERENCES "trips"("id"),
	FOREIGN KEY("user_id") REFERENCES "users"("id")
);
CREATE TABLE IF NOT EXISTS "trips" (
	"id"	INTEGER,
	"company_id"	INTEGER NOT NULL,
	"destination_city"	INTEGER NOT NULL,
	"arrival_time"	TEXT NOT NULL,
	"departure_city"	INTEGER NOT NULL,
	"departure_time"	TEXT NOT NULL,
	"price"	INTEGER NOT NULL,
	"capacity"	INTEGER NOT NULL,
	"created_at"	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id"),
	FOREIGN KEY("company_id") REFERENCES "companies"("id"),
	FOREIGN KEY("departure_city") REFERENCES "cities"("id"),
	FOREIGN KEY("destination_city") REFERENCES "cities"("id")
);
CREATE TABLE IF NOT EXISTS "user_coupons" (
	"id"	INTEGER,
	"coupon_id"	INTEGER NOT NULL,
	"user_id"	INTEGER NOT NULL,
	"created_at"	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	UNIQUE("coupon_id","user_id"),
	PRIMARY KEY("id"),
	FOREIGN KEY("coupon_id") REFERENCES "coupons"("id"),
	FOREIGN KEY("user_id") REFERENCES "users"("id")
);
CREATE TABLE IF NOT EXISTS "users" (
	"id"	INTEGER,
	"full_name"	TEXT NOT NULL,
	"email"	TEXT NOT NULL UNIQUE,
	"role"	TEXT NOT NULL CHECK("role" IN ('user', 'company', 'admin')),
	"password"	TEXT NOT NULL,
	"company_id"	INTEGER,
	"balance"	REAL DEFAULT 800,
	"created_at"	TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id"),
	FOREIGN KEY("company_id") REFERENCES "companies"("id")
);
INSERT INTO "booked_seats" VALUES (1,1,10,'2025-10-14 09:37:25');
INSERT INTO "booked_seats" VALUES (3,3,8,'2025-10-16 19:03:26');
INSERT INTO "booked_seats" VALUES (4,6,9,'2025-10-16 20:01:35');
INSERT INTO "booked_seats" VALUES (6,9,10,'2025-10-18 09:53:35');
INSERT INTO "booked_seats" VALUES (9,13,11,'2025-10-19 09:39:19');
INSERT INTO "booked_seats" VALUES (10,14,14,'2025-10-19 12:09:55');
INSERT INTO "booked_seats" VALUES (12,20,3,'2025-10-20 14:54:08');
INSERT INTO "booked_seats" VALUES (13,21,10,'2025-10-20 20:19:36');
INSERT INTO "booked_seats" VALUES (14,22,1,'2025-10-21 10:21:42');
INSERT INTO "booked_seats" VALUES (15,23,11,'2025-10-21 11:08:34');
INSERT INTO "booked_seats" VALUES (16,25,28,'2025-10-22 07:41:41');
INSERT INTO "booked_seats" VALUES (17,26,27,'2025-10-22 07:42:48');
INSERT INTO "booked_seats" VALUES (18,28,1,'2025-10-22 23:07:12');
INSERT INTO "booked_seats" VALUES (19,29,1,'2025-10-22 23:08:36');
INSERT INTO "cities" VALUES (1,'Çorum','2025-10-07 09:26:49');
INSERT INTO "cities" VALUES (2,'Destination City','2025-10-21 12:00:00');
INSERT INTO "cities" VALUES (3,'Antalya','2025-10-07 09:58:06');
INSERT INTO "cities" VALUES (4,'Ankara','2025-10-07 09:59:55');
INSERT INTO "cities" VALUES (5,'Zonguldak','2025-10-07 10:00:00');
INSERT INTO "cities" VALUES (6,'İstanbul','2025-10-07 10:00:07');
INSERT INTO "cities" VALUES (7,'Mersin','2025-10-07 10:00:12');
INSERT INTO "cities" VALUES (8,'Kocaeli','2025-10-07 10:00:18');
INSERT INTO "cities" VALUES (9,'Manisa','2025-10-07 10:00:28');
INSERT INTO "cities" VALUES (10,'Kastamonu','2025-10-07 10:00:32');
INSERT INTO "cities" VALUES (11,'Kırıkkale','2025-10-07 10:00:47');
INSERT INTO "cities" VALUES (12,'Bursa','2025-10-07 10:00:50');
INSERT INTO "cities" VALUES (13,'Konya','2025-10-07 10:00:56');
INSERT INTO "cities" VALUES (14,'İzmir','2025-10-07 10:46:54');
INSERT INTO "cities" VALUES (15,'Amasya','2025-10-07 10:47:03');
INSERT INTO "cities" VALUES (16,'Artvin','2025-10-07 10:47:11');
INSERT INTO "cities" VALUES (17,'Van','2025-10-07 10:47:20');
INSERT INTO "cities" VALUES (18,'Bolu','2025-10-07 10:47:40');
INSERT INTO "cities" VALUES (19,'Sinop','2025-10-07 10:47:43');
INSERT INTO "cities" VALUES (20,'Karaman','2025-10-07 10:47:52');
INSERT INTO "cities" VALUES (22,'Eskişehir','2025-10-07 10:48:07');
INSERT INTO "cities" VALUES (23,'Karabük','2025-10-07 10:48:15');
INSERT INTO "cities" VALUES (25,'Kütahya','2025-10-07 10:48:26');
INSERT INTO "cities" VALUES (26,'Tokat','2025-10-07 22:30:23');
INSERT INTO "cities" VALUES (27,'Iğdır','2025-10-18 21:42:13');
INSERT INTO "cities" VALUES (28,'Samsun','2025-10-23 10:14:06');
INSERT INTO "companies" VALUES (4,'Varan Turizm','https://s3.eu-central-1.amazonaws.com/static.obilet.com/images/partner/32285-sm.png','2025-10-07 22:58:37');
INSERT INTO "companies" VALUES (6,'Ali Osman Ulusoy','https://s3.eu-central-1.amazonaws.com/static.obilet.com/images/partner/32344-sm.png','2025-10-07 22:59:16');
INSERT INTO "companies" VALUES (7,'Tokat Yıldızı Seyehat','https://s3.eu-central-1.amazonaws.com/static.obilet.com/images/partner/32292-sm.png','2025-10-07 23:12:56');
INSERT INTO "companies" VALUES (8,'Kamil Koç','https://s3.eu-central-1.amazonaws.com/static.obilet.com/images/partner/3195-sm.png','2025-10-07 23:13:52');
INSERT INTO "companies" VALUES (9,'Metro','https://s3.eu-central-1.amazonaws.com/static.obilet.com/images/partner/330-sm.png','2025-10-10 14:42:33');
INSERT INTO "companies" VALUES (10,'Tokat Seyehat','https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSQF3Ra-YH5do6HaOYETjZxZcO5xmYVY21AxQ&s','2025-10-18 22:43:28');
INSERT INTO "companies" VALUES (11,'Pamukkale Seyehat','https://s3.eu-central-1.amazonaws.com/static.obilet.com/images/partner/3421-sm.png','2025-10-21 10:12:53');
INSERT INTO "coupons" VALUES (1,'Z3ECFQCUGS',0.2,1,1,'2025-10-21 23:59:59',NULL,'2025-10-18 21:51:53','active');
INSERT INTO "coupons" VALUES (5,'P4JJKJ3678',0.2,10,0,'2025-10-22 23:59:59',7,'2025-10-20 08:57:22','active');
INSERT INTO "coupons" VALUES (6,'X7CTL75A2R',0.3,10,0,'2025-10-22 23:59:59',9,'2025-10-20 08:58:11','active');
INSERT INTO "coupons" VALUES (8,'W1705POK8D',0.1,5,2,'2025-10-22 23:59:59',4,'2025-10-20 14:51:01','active');
INSERT INTO "coupons" VALUES (9,'PAMUKKALE20',0.2,5,1,'2025-10-25 23:59:59',11,'2025-10-21 10:20:38','active');
INSERT INTO "coupons" VALUES (11,'METRO10',0.1,5,2,'2025-10-28 23:59:59',9,'2025-10-22 22:53:39','active');
INSERT INTO "tickets" VALUES (1,8,1,600.0,'canceled','2025-10-14 09:37:25',600,NULL,NULL);
INSERT INTO "tickets" VALUES (2,10,1,500.0,'canceled','2025-10-16 19:01:58',500,NULL,NULL);
INSERT INTO "tickets" VALUES (3,10,5,500.0,'active','2025-10-16 19:03:26',500,NULL,NULL);
INSERT INTO "tickets" VALUES (4,10,9,500.0,'canceled','2025-10-16 19:07:25',500,NULL,NULL);
INSERT INTO "tickets" VALUES (5,12,9,300.0,'canceled','2025-10-16 19:23:39',300,NULL,NULL);
INSERT INTO "tickets" VALUES (6,12,9,300.0,'active','2025-10-16 20:01:35',300,NULL,NULL);
INSERT INTO "tickets" VALUES (7,11,6,450.0,'canceled','2025-10-16 20:28:05',450,NULL,NULL);
INSERT INTO "tickets" VALUES (8,19,1,300.0,'canceled','2025-10-16 21:36:25',300,NULL,NULL);
INSERT INTO "tickets" VALUES (9,12,1,300.0,'active','2025-10-18 09:53:35',300,NULL,NULL);
INSERT INTO "tickets" VALUES (10,17,9,400.0,'canceled','2025-10-18 12:42:09',400,NULL,NULL);
INSERT INTO "tickets" VALUES (11,13,5,300.0,'canceled','2025-10-18 23:27:51',300,NULL,NULL);
INSERT INTO "tickets" VALUES (12,20,1,450.0,'canceled','2025-10-19 09:37:37',360,NULL,NULL);
INSERT INTO "tickets" VALUES (13,13,5,300.0,'active','2025-10-19 09:39:19',270,NULL,NULL);
INSERT INTO "tickets" VALUES (14,15,9,400.0,'active','2025-10-19 12:09:55',400,NULL,NULL);
INSERT INTO "tickets" VALUES (15,15,1,400.0,'canceled','2025-10-19 12:49:59',200,NULL,NULL);
INSERT INTO "tickets" VALUES (16,15,1,400.0,'canceled','2025-10-20 09:18:49',200,NULL,NULL);
INSERT INTO "tickets" VALUES (17,13,1,300.0,'canceled','2025-10-20 11:47:17',300,NULL,NULL);
INSERT INTO "tickets" VALUES (18,13,1,300.0,'canceled','2025-10-20 13:05:36',225,NULL,NULL);
INSERT INTO "tickets" VALUES (19,26,7,420.0,'canceled','2025-10-20 14:53:13',378,NULL,NULL);
INSERT INTO "tickets" VALUES (20,26,1,420.0,'active','2025-10-20 14:54:08',378,NULL,NULL);
INSERT INTO "tickets" VALUES (21,14,1,400.0,'active','2025-10-20 20:19:36',400,NULL,NULL);
INSERT INTO "tickets" VALUES (22,27,9,500.0,'active','2025-10-21 10:21:42',400,NULL,NULL);
INSERT INTO "tickets" VALUES (23,28,9,500.0,'active','2025-10-21 11:08:34',400,10,'ALİOSMAN20');
INSERT INTO "tickets" VALUES (24,29,12,400.0,'canceled','2025-10-22 07:40:11',400,NULL,NULL);
INSERT INTO "tickets" VALUES (25,29,12,400.0,'active','2025-10-22 07:41:41',320,NULL,NULL);
INSERT INTO "tickets" VALUES (26,29,12,400.0,'active','2025-10-22 07:42:48',400,NULL,NULL);
INSERT INTO "tickets" VALUES (27,29,9,300.0,'canceled','2025-10-22 22:55:31',270,NULL,NULL);
INSERT INTO "tickets" VALUES (28,29,9,300.0,'active','2025-10-22 23:07:12',270,11,'METRO10');
INSERT INTO "tickets" VALUES (29,30,9,400.0,'active','2025-10-22 23:08:36',400,NULL,'');
INSERT INTO "trips" VALUES (4,6,18,'2025-10-10 21:43:00',23,'2025-10-10 16:43:00',850,64,'2025-10-10 13:44:14');
INSERT INTO "trips" VALUES (5,6,4,'2025-10-14T08:05',14,'2025-10-13T17:05',1250,32,'2025-10-10 14:05:51');
INSERT INTO "trips" VALUES (6,4,18,'2025-10-18T01:34',22,'2025-10-18T17:34',1500,64,'2025-10-10 14:35:16');
INSERT INTO "trips" VALUES (8,9,4,'2025-10-15 16:12:00',10,'2025-10-15 11:12:00',600,32,'2025-10-14 08:12:57');
INSERT INTO "trips" VALUES (9,4,4,'2025-10-18T16:14',10,'2025-10-18T11:13',600,32,'2025-10-14 08:14:21');
INSERT INTO "trips" VALUES (10,4,4,'2025-10-19 02:00:00',10,'2025-10-18 22:00:00',500,32,'2025-10-16 18:59:49');
INSERT INTO "trips" VALUES (11,9,4,'2025-10-18 12:45:00',10,'2025-10-18 08:30:00',450,32,'2025-10-16 19:18:51');
INSERT INTO "trips" VALUES (13,6,10,'2025-10-21 15:30:00',4,'2025-10-21 11:00:00',300,32,'2025-10-16 20:07:52');
INSERT INTO "trips" VALUES (14,6,10,'2025-10-21 18:00:00',4,'2025-10-21 14:00:00',400,32,'2025-10-16 20:17:13');
INSERT INTO "trips" VALUES (15,6,10,'2025-10-21 15:30:00',4,'2025-10-21 10:00:00',400,32,'2025-10-16 20:22:52');
INSERT INTO "trips" VALUES (17,6,10,'2025-10-21 15:30:00',4,'2025-10-21 10:00:00',400,32,'2025-10-16 20:25:58');
INSERT INTO "trips" VALUES (19,1,2,'2030-01-01 12:00',1,'2030-01-01 08:00',100,50,'2025-10-21 09:50:30');
INSERT INTO "trips" VALUES (20,4,10,'2025-10-21 16:34:00',4,'2025-10-21 12:34:00',450,32,'2025-10-16 20:38:55');
INSERT INTO "trips" VALUES (21,6,3,'2025-10-18 11:00:00',12,'2025-10-17 12:30:00',1480,30,'2025-10-16 21:39:18');
INSERT INTO "trips" VALUES (22,6,1,'2025-10-18 01:30:00',26,'2025-10-17 21:00:00',500,30,'2025-10-16 21:42:36');
INSERT INTO "trips" VALUES (23,6,10,'2025-10-17 13:30:00',19,'2025-10-17 10:20:00',500,30,'2025-10-16 21:48:26');
INSERT INTO "trips" VALUES (25,6,10,'2025-10-17 13:30:00',19,'2025-10-17 10:20:00',500,30,'2025-10-16 21:49:49');
INSERT INTO "trips" VALUES (26,4,22,'2025-10-21 04:30:00',6,'2025-10-20 20:00:00',420,28,'2025-10-20 14:52:09');
INSERT INTO "trips" VALUES (27,11,19,'2025-10-22 17:00:00',4,'2025-10-22 09:00:00',500,28,'2025-10-21 10:18:02');
INSERT INTO "trips" VALUES (28,6,10,'2025-10-21 21:00:00',4,'2025-10-21 17:00:00',500,32,'2025-10-21 11:07:01');
INSERT INTO "trips" VALUES (29,9,14,'2025-10-24T20:00',10,'2025-10-24T08:00',300,32,'2025-10-22 22:19:06');
INSERT INTO "trips" VALUES (30,9,14,'2025-10-25 22:00:00',10,'2025-10-25 10:00:00',400,32,'2025-10-22 22:52:42');
INSERT INTO "users" VALUES (1,'irem şen','iremsen@gmail.com','user','$2y$10$O1izlMNDAh.w1L5dqFkUdefTHP.fIUxT4VHYFwUY6xhK4jW37HYkC',NULL,722.0,'2025-10-05 10:27:04');
INSERT INTO "users" VALUES (4,'admin','admin@ticketbox.com','admin','$2y$10$cf1PhrQddIODT94ZV9wRx.ytDeCowQy6tdZubXAwqrToPB5Lk2gUi',NULL,800.0,'2025-10-05 21:28:32');
INSERT INTO "users" VALUES (5,'Zehranur Gülşin','zehra1@gmail.com','user','$2y$10$uv6o2E2gpN92wl5wt10gkuK/4hBahy4Y4kWdPwCWZtby/nHzw/xf.',NULL,30.0,'2025-10-07 20:46:30');
INSERT INTO "users" VALUES (6,'Ali Osman Ulusoy','aliosmanulusoy@ticketbox.com','company','$2y$10$rdI5uO7ptC/ZfMDq0EVx8OB3SEfxwPVt66i7DTHJ.u1n68bI7e8oC',6,800.0,'2025-10-10 02:43:18');
INSERT INTO "users" VALUES (7,'Varan Turizm','varan@ticketbox.com','company','$2y$10$7Qbj.mbyORT5IFlTdnCCAOTvO2ieFmEJdkteTUC7hK6IURHg4LdfK',4,800.0,'2025-10-10 14:34:02');
INSERT INTO "users" VALUES (8,'Metro Turizm','metro@ticketbox.com','company','$2y$10$xp4XE4.qnRBODGM.Ddi6Sud3HDMpgWZETIzcRNbVroTnIqDW.vfmG',9,800.0,'2025-10-10 14:43:16');
INSERT INTO "users" VALUES (9,'Ayşe Nilgün','aysenlgn@gmail.com','user','$2y$10$bv1aB8ishjUCEP2OuFWPwuxZX0EhmoLYXmyF2YhV9yAOQaN5k2dke',NULL,980.0,'2025-10-16 19:06:23');
INSERT INTO "users" VALUES (10,'Tokat Seyehat','tokatseyehat@ticketbox.com','company','$2y$10$g1MiH6hcaVh44zLXVgfI2OSuNbx7avacwXmv5ExD7a6mlleOw5.rW',10,800.0,'2025-10-18 22:44:29');
INSERT INTO "users" VALUES (11,'Pamukkale Seyehat','pamukkale@ticketbox.com','company','$2y$10$pftxgcAGF/H8S5Xvoxu48O2p4OaS1yuh9z9MP/EjrRAgqdOmDRwJG',11,800.0,'2025-10-21 10:13:40');
INSERT INTO "users" VALUES (12,'Zehranur Gülşin','zehranur@gmail.com','user','$2y$10$0wyxH/K14EhZTuLSQuIq/uU3WD/6be0saitu/cV2WVfne.hZ40rA.',NULL,2302.0,'2025-10-22 07:39:31');
INSERT INTO "users" VALUES (13,'İremnaz Sen','iremnsen0@gmail.com','user','$2y$10$BM3pPE5Y7QrsVL7dx.6g.ezTmeTMzcCvqtz1UxqTAYId1uYzWSC9u',NULL,1800.0,'2025-10-23 00:01:36');
COMMIT;
