-- create database
DROP DATABASE IF EXISTS TestingSystem;
CREATE DATABASE TestingSystem;
USE TestingSystem;

-- create table 1: Department
DROP TABLE IF EXISTS Department;
CREATE TABLE Department(
	DepartmentID 			TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    DepartmentName 			NVARCHAR(30) NOT NULL UNIQUE KEY
);

-- create table 2: Posittion
DROP TABLE IF EXISTS Position;
CREATE TABLE `Position` (
	PositionID				TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    PositionName			ENUM('DEV','TEST','SCRUMMASTER','PM') NOT NULL UNIQUE KEY -- mapping trên Java là String
);

-- create table 2: Posittion
DROP TABLE IF EXISTS Salary;
CREATE TABLE `Salary` (
	SalaryID			TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    SalaryName			INT UNSIGNED CHECK (SalaryName = 600 OR SalaryName = 700 OR SalaryName = 1500 OR SalaryName = 2000) NOT NULL UNIQUE KEY -- 600: Dev, Test: 700, Scrum Master: 1500, PM: 2000
);

-- create table 3: Account
DROP TABLE IF EXISTS `Account`;
CREATE TABLE `Account`(
	AccountID				TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Email					VARCHAR(50) NOT NULL UNIQUE KEY, -- Cannot update this field
    Username				VARCHAR(50) NOT NULL UNIQUE KEY, -- Cannot update this field
    FirstName				NVARCHAR(50) NOT NULL,
    LastName				NVARCHAR(50) NOT NULL,	-- create field fullName in POJO
    CreateDate				DATETIME DEFAULT NOW() -- Cannot update this field
);

-- create table 4: Group
DROP TABLE IF EXISTS `Group`;
CREATE TABLE `Group`(
	GroupID					TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    GroupName				NVARCHAR(50) NOT NULL UNIQUE KEY,
    CreateDate				DATETIME DEFAULT NOW() -- Cannot update this field
);

-- create table 6: TypeQuestion
DROP TABLE IF EXISTS TypeQuestion;
CREATE TABLE TypeQuestion (
    TypeID 			TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    TypeName 		BIT NOT NULL UNIQUE KEY -- 0:  Essay, 1: Multiple-Choice
);

-- create table 7: CategoryQuestion
DROP TABLE IF EXISTS CategoryQuestion;
CREATE TABLE CategoryQuestion(
    CategoryID				TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
    CategoryName			NVARCHAR(50) NOT NULL UNIQUE KEY
);

-- create table 8: Question
DROP TABLE IF EXISTS Question;
CREATE TABLE Question(
    QuestionID				TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Content					NVARCHAR(100) NOT NULL,
    CreateDate				DATETIME DEFAULT NOW() -- Cannot update this field
);

-- create table 9: Answer
DROP TABLE IF EXISTS Answer;
CREATE TABLE Answer(
    Answers					TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Content					NVARCHAR(100) NOT NULL,
    isCorrect				BIT DEFAULT 1
);

-- create table 10: Exam
DROP TABLE IF EXISTS Exam;
CREATE TABLE Exam (
    ExamID					TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `Code1`					CHAR(10) NOT NULL,	-- L-1: if duration >= 180p, M-1: if duration >= 90p, S-1: otherwise -- Cannot update this field
    `Code2`					CHAR(10), -- NOT NULL,	-- L-1: if duration >= 180p, M-1: if duration >= 90p, S-1: otherwise -- Cannot update this field
    Title					NVARCHAR(50) NOT NULL,
    Duration				TINYINT UNSIGNED NOT NULL DEFAULT 45,
    CreateDate				DATETIME DEFAULT NOW() -- Cannot update this field
);