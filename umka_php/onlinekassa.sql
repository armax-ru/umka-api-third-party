-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Мар 18 2019 г., 10:49
-- Версия сервера: 5.6.38
-- Версия PHP: 7.2.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- База данных: `kassa`
--

-- --------------------------------------------------------

--
-- Структура таблицы `onlinekassa`
--

CREATE TABLE `onlinekassa` (
  `id` int(11) NOT NULL,
  `paym_id` text CHARACTER SET cp1251 NOT NULL,
  `datetime` datetime NOT NULL,
  `fn_number` text CHARACTER SET cp1251 NOT NULL,
  `onkassa` text CHARACTER SET cp1251 NOT NULL,
  `onOFD` text CHARACTER SET cp1251 NOT NULL,
  `summ` float NOT NULL,
  `client` text CHARACTER SET cp1251 NOT NULL,
  `fd` text CHARACTER SET cp1251 NOT NULL,
  `fpd` text CHARACTER SET cp1251 NOT NULL,
  `fisc_type` text CHARACTER SET cp1251 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Дамп данных таблицы `onlinekassa`
--

INSERT INTO `onlinekassa` (`id`, `paym_id`, `datetime`, `fn_number`, `onkassa`, `onOFD`, `summ`, `client`, `fd`, `fpd`, `fisc_type`) VALUES
(1, '9236fg9263fd92qy3gd', '2019-03-18 15:34:40', '9999078900003063', 'OK', 'OK', 1, '1@kshfbv.ri', '831', '4254813444', 'ПРИХОД');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `onlinekassa`
--
ALTER TABLE `onlinekassa`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `onlinekassa`
--
ALTER TABLE `onlinekassa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
