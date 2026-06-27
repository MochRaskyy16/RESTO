-- =============================================
-- BACKUP DATABASE: menu_resto
-- TANGGAL: 2026-06-25 12:38:28
-- =============================================

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `admin` VALUES
('1','admin','0192023a7bbd73250516f069df18b500','Administrator','2026-06-12 12:55:41');

DROP TABLE IF EXISTS `detail_pesanan`;
CREATE TABLE `detail_pesanan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_pesanan` int NOT NULL,
  `id_menu` int NOT NULL,
  `qty` int NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_pesanan` (`id_pesanan`),
  KEY `id_menu` (`id_menu`),
  CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `detail_pesanan` VALUES
('1','1','10','1','50000.00'),
('2','1','3','1','5000.00'),
('3','1','11','1','35000.00'),
('4','1','17','1','90000.00'),
('7','3','17','1','90000.00'),
('8','3','11','1','35000.00'),
('9','4','16','1','40000.00'),
('10','4','17','1','90000.00'),
('11','4','11','1','35000.00'),
('12','5','17','4','90000.00'),
('19','9','17','1','90000.00'),
('20','9','16','1','40000.00');

DROP TABLE IF EXISTS `detail_transaksi`;
CREATE TABLE `detail_transaksi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_transaksi` int NOT NULL,
  `id_menu` int NOT NULL,
  `qty` int NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_transaksi` (`id_transaksi`),
  KEY `id_menu` (`id_menu`),
  CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detail_transaksi_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `detail_transaksi` VALUES
('13','4','3','1','5000.00'),
('16','6','3','4','20000.00'),
('18','7','3','3','15000.00'),
('22','11','3','3','15000.00'),
('23','11','2','4','112000.00'),
('26','13','1','2','70000.00'),
('28','14','1','2','70000.00'),
('29','14','3','2','10000.00'),
('33','15','3','2','10000.00'),
('34','17','2','4','112000.00'),
('37','18','2','3','84000.00'),
('38','19','3','4','20000.00'),
('39','19','1','4','140000.00'),
('40','19','2','2','56000.00'),
('41','20','1','2','70000.00'),
('42','20','3','4','20000.00'),
('44','21','2','2','56000.00'),
('45','21','3','4','20000.00'),
('47','22','1','4','140000.00'),
('48','22','2','3','84000.00'),
('51','24','1','4','140000.00'),
('53','27','1','3','105000.00'),
('54','27','3','2','10000.00'),
('57','1','1','1','35000.00'),
('58','2','1','1','35000.00'),
('59','3','1','4','35000.00'),
('60','4','1','4','35000.00'),
('61','5','1','1','35000.00'),
('62','6','1','3','35000.00'),
('63','7','1','1','35000.00'),
('64','8','1','5','35000.00'),
('65','9','1','4','35000.00'),
('66','10','1','2','35000.00'),
('67','11','1','5','35000.00'),
('68','12','1','1','35000.00'),
('69','13','1','2','35000.00'),
('70','14','1','1','35000.00'),
('71','15','1','4','35000.00'),
('72','16','1','3','35000.00'),
('73','17','1','5','35000.00'),
('74','18','1','5','35000.00'),
('75','19','1','1','35000.00'),
('76','20','1','4','35000.00'),
('77','21','1','3','35000.00'),
('78','22','1','5','35000.00'),
('79','23','1','5','35000.00'),
('80','24','1','5','35000.00'),
('81','25','1','3','35000.00'),
('82','26','1','1','35000.00'),
('83','27','1','5','35000.00'),
('84','28','1','1','35000.00'),
('85','29','1','1','35000.00');

DROP TABLE IF EXISTS `menu`;
CREATE TABLE `menu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_menu` varchar(100) NOT NULL,
  `kategori` enum('Makanan','Minuman','Snack','Dessert') NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `deskripsi` text,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `menu` VALUES
('1','Burger Akbar','Snack','35000.00','buatan khas mang Akbar','1781267102_6a2bfa9e08522.jpg','2026-06-03 11:27:35'),
('2','coffe capucino','Minuman','28000.00','buatan barista fugi, sang ahli dalam bidang per coffean','1781267016_6a2bfa4827b2d.jpg','2026-06-03 11:27:35'),
('3','bolu ','Dessert','5000.00','asli buatan mamah zidane','1781266922_6a2bf9ea3a00a.png','2026-06-03 11:27:35'),
('10','cumi asam manis','Makanan','50000.00','cumi masakan zidane bachtiar, chief asli dari thailand','1781266816_6a2bf98021ec7.jpg','2026-06-10 05:21:22'),
('11','soto','Makanan','35000.00','soto khas samarang','1781266766_6a2bf94e2a30f.png','2026-06-12 07:33:23'),
('16','pasta legit','Makanan','40000.00','buatan asli cheif sani','1781267162_6a2bfada9cb8d.jpg','2026-06-12 12:26:02'),
('17','Pizza D Rasky','Makanan','90000.00','Pizza Premium Import dari Paris','1781267247_6a2bfb2f50809.jpg','2026-06-12 12:27:27'),
('18','kentang','Snack','15000.00','kentang buatan Chief dzikri, khas inggris','1781272781_6a2c10cd057da.jpg','2026-06-12 13:58:39'),
('20','Matcha','Minuman','25000.00','Matcha khas  buatan india','1781362597_6a2d6fa52193b.jpg','2026-06-13 14:56:37'),
('21','hotdog','Snack','18000.00','enak','1781390852_6a2dde042d1cd.jpg','2026-06-13 22:47:32'),
('22','sushi','Makanan','20000.00','legit enak mantap','1781396562_6a2df452676c1.jpg','2026-06-14 00:22:42'),
('23','Burger','Snack','15000.00','Burger original','1781396765_6a2df51d33791.png','2026-06-14 00:26:05'),
('24','puding legit','Dessert','15000.00','enak manis','1781397316_6a2df744a92ec.jpg','2026-06-14 00:35:16');

DROP TABLE IF EXISTS `pesanan`;
CREATE TABLE `pesanan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_pesanan` varchar(20) NOT NULL,
  `nama_pemesan` varchar(100) NOT NULL,
  `no_meja` varchar(10) DEFAULT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `status` enum('pending','proses','selesai','batal') DEFAULT 'pending',
  `tanggal_pesan` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `catatan` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_pesanan` (`no_pesanan`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `pesanan` VALUES
('1','INV202606124176','Albar','02','180000.00','selesai','2026-06-12 13:29:09','cuminya jangan terlalu pedas'),
('3','INV202606132680','andre','09','125000.00','selesai','2026-06-13 15:16:24','cepetan'),
('4','INV202606134708','zidan','09','165000.00','selesai','2026-06-13 22:44:25','ulah lada teuing'),
('5','INV202606143775','piki','03','360000.00','selesai','2026-06-14 00:31:19','jangan terlalu matang'),
('9','INV202606235700','zidan','5','130000.00','selesai','2026-06-23 02:10:03','enak');

DROP TABLE IF EXISTS `transaksi`;
CREATE TABLE `transaksi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `no_meja` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `transaksi` VALUES
('1','2026-06-03','30000.00','M1','2026-06-04 09:51:44'),
('2','2026-06-03','75000.00','M2','2026-06-04 09:51:44'),
('3','2026-06-03','45000.00','M3','2026-06-04 09:51:44'),
('4','2026-06-02','90000.00','M1','2026-06-04 09:51:44'),
('5','2026-06-02','85000.00','M4','2026-06-04 09:51:44'),
('6','2026-06-02','35000.00','M2','2026-06-04 09:51:44'),
('7','2026-06-01','15000.00','M3','2026-06-04 09:51:44'),
('8','2026-06-01','40000.00','M1','2026-06-04 09:51:44'),
('9','2026-06-01','30000.00','M2','2026-06-04 09:51:44'),
('10','2026-05-31','15000.00','M4','2026-06-04 09:51:44'),
('11','2026-05-31','137000.00','M1','2026-06-04 09:51:44'),
('12','2026-05-31','30000.00','M3','2026-06-04 09:51:44'),
('13','2026-05-30','70000.00','M2','2026-06-04 09:51:44'),
('14','2026-05-30','185000.00','M1','2026-06-04 09:51:44'),
('15','2026-05-29','70000.00','M3','2026-06-04 09:51:44'),
('16','2026-05-29','0.00','M4','2026-06-04 09:51:44'),
('17','2026-05-29','112000.00','M1','2026-06-04 09:51:44'),
('18','2026-05-28','139000.00','M2','2026-06-04 09:51:44'),
('19','2026-05-28','216000.00','M3','2026-06-04 09:51:44'),
('20','2026-05-27','90000.00','M1','2026-06-04 09:51:44'),
('21','2026-05-27','161000.00','M4','2026-06-04 09:51:44'),
('22','2026-05-27','224000.00','M2','2026-06-04 09:51:44'),
('23','2026-06-04','60000.00','M1','2026-06-04 09:51:44'),
('24','2026-06-05','170000.00','M3','2026-06-04 09:51:44'),
('25','2026-06-06','40000.00','M2','2026-06-04 09:51:44'),
('26','2026-06-07','0.00','M4','2026-06-04 09:51:44'),
('27','2026-06-08','115000.00','M1','2026-06-04 09:51:44'),
('28','2026-06-09','85000.00','M3','2026-06-04 09:51:44'),
('29','2026-06-10','0.00','M2','2026-06-04 09:51:44'),
('30','2026-06-17','150000.00','M1','2026-06-23 01:35:26'),
('31','2026-06-18','200000.00','M2','2026-06-23 01:35:26'),
('32','2026-06-19','175000.00','M3','2026-06-23 01:35:26'),
('33','2026-06-20','250000.00','M1','2026-06-23 01:35:26'),
('34','2026-06-21','180000.00','M2','2026-06-23 01:35:26'),
('35','2026-06-22','220000.00','M3','2026-06-23 01:35:26'),
('36','2026-06-23','300000.00','M1','2026-06-23 01:35:26');

SET FOREIGN_KEY_CHECKS=1;
