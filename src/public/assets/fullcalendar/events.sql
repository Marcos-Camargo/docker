DROP TABLE `calendar_events`;
CREATE TABLE `calendar_events` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
 `title` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
 `event_type` TINYINT NOT NULL default 0,
 `start` datetime NOT NULL,
 `end` datetime NOT NULL,
 `module_path` varchar(50) NOT NULL,
 `module_method` varchar(50) NOT NULL,
 `params` varchar(1000) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
 
 INSERT INTO `calendar_events` (`ID`, `title`,`event_type`, `start`, `end`, `module_path`,`module_method`,`params`) VALUES
(1, 'Bling - Produtos', 71,'2019-07-23 00:00:00', '2200-12-31 23:59:00', 'batch', 'blingprodutos', 'hoje'),
(2, 'Bling - Pedidos', 5,'2019-07-23 00:00:00', '2200-12-31 23:59:00', 'batch', 'blingorders', 'hoje');