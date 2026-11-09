-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 02 ديسمبر 2025 الساعة 06:53
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lms_db`
--

-- --------------------------------------------------------

--
-- بنية الجدول `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_by` varchar(255) NOT NULL,
  `due_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `assignments`
--

INSERT INTO `assignments` (`id`, `course_id`, `title`, `description`, `file_path`, `created_by`, `due_date`, `created_at`) VALUES
(2, 5, 'assignment 1', 'Information about Deliverables: You will submit a pdf file for the answers to questions that do \r\nnot require programming. For the answers consisting of implementation in Java, you will submit \r\nthe .java files that you worked on and completed. Please ensure that you follow these submission \r\ninstructions while submitting your Assignment#3 on Canvas.', 'uploads/assignments/1763951142_Assignment 3.pdf', 'khaled', '2025-12-24 05:29:20', '2025-11-24 02:25:42');

-- --------------------------------------------------------

--
-- بنية الجدول `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_username` varchar(100) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `grade` varchar(10) DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(100) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `credit_hours` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `student_count` int(11) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `courses`
--

INSERT INTO `courses` (`course_id`, `course_name`, `course_code`, `credit_hours`, `description`, `content`, `student_count`, `created_by`) VALUES
(5, 'Data Structures', 'CS210', 3, 'This course covers essential data structures used in computer science to store and manage data efficiently. Students learn how different structures work, how to implement them, and how to choose the right one to solve programming problems.', '🎯 Learning Outcomes\r\n\r\nBy the end of this course, students will be able to:\r\n\r\nUnderstand the purpose and use of common data structures\r\n\r\nImplement linked lists, stacks, queues, trees, and graphs\r\n\r\nAnalyze algorithms using Big-O notation\r\n\r\nChoose appropriate data structures for solving specific problems\r\n\r\n📚 Topics Covered\r\n\r\nArrays & Linked Lists\r\n\r\nStacks & Queues\r\n\r\nTrees (BST, AVL, Heap)\r\n\r\nHash Tables\r\n\r\nGraphs (BFS, DFS)\r\n\r\nBig-O Analysis\r\n\r\nRecursion', NULL, 'khaled@mail.com');

-- --------------------------------------------------------

--
-- بنية الجدول `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`) VALUES
(12, 6, 5);

-- --------------------------------------------------------

--
-- بنية الجدول `exams`
--

CREATE TABLE `exams` (
  `exam_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `questions` text NOT NULL,
  `course_id` int(11) NOT NULL,
  `created_by` varchar(255) NOT NULL,
  `start_datetime` datetime DEFAULT NULL,
  `end_datetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `exams`
--

INSERT INTO `exams` (`exam_id`, `title`, `questions`, `course_id`, `created_by`, `start_datetime`, `end_datetime`) VALUES
(5, 'Data Structures Exam', '[\r\n        {\r\n            \"type\": \"true_false\",\r\n            \"text\": \"Elements in an Array are accessed using an index.\",\r\n            \"answer\": \"True\"\r\n        },\r\n        {\r\n            \"type\": \"true_false\",\r\n            \"text\": \"A Linked List allows random access to its elements.\",\r\n            \"answer\": \"False\"\r\n        },\r\n        {\r\n            \"type\": \"true_false\",\r\n            \"text\": \"A Stack works based on FIFO.\",\r\n            \"answer\": \"False\"\r\n        },\r\n        {\r\n            \"type\": \"true_false\",\r\n            \"text\": \"In a Binary Search Tree, the left child is smaller than the root.\",\r\n            \"answer\": \"True\"\r\n        },\r\n        {\r\n            \"type\": \"multiple_choice\",\r\n            \"text\": \"Which data structure follows the LIFO principle?\",\r\n            \"choices\": [\"Queue\", \"Stack\", \"Array\", \"Tree\"],\r\n            \"answer\": \"Stack\"\r\n        },\r\n        {\r\n            \"type\": \"multiple_choice\",\r\n            \"text\": \"What is the time complexity of Binary Search?\",\r\n            \"choices\": [\"O(n)\", \"O(n²)\", \"O(log n)\", \"O(1)\"],\r\n            \"answer\": \"O(log n)\"\r\n        },\r\n        {\r\n            \"type\": \"essay\",\r\n            \"text\": \"Compare Arrays and Linked Lists in terms of storage, insertion/deletion, and access speed.\",\r\n            \"answer\": \"Arrays use continuous memory and allow fast access but slow insert/delete. Linked Lists use node pointers, slow access, but faster insert/delete.\"\r\n        }\r\n    ]', 5, 'khaled@mail.com', '2025-11-23 10:00:00', '2025-12-24 11:00:00');

-- --------------------------------------------------------

--
-- بنية الجدول `exam_submissions`
--

CREATE TABLE `exam_submissions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_username` varchar(255) NOT NULL,
  `answers` text NOT NULL,
  `score` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `instructors`
--

CREATE TABLE `instructors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `major` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `instructors`
--

INSERT INTO `instructors` (`id`, `name`, `email`, `username`, `phone`, `major`, `password`) VALUES
(3, 'khaled', 'khaled@mail.com', 'khaled', '966568935876', 'computer science', '$2y$10$IloFfT70HcJfGgKYABsux.rLXJ.DP8G3EJSyOY1RxxkUs/OGwvqz6');

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `username`, `phone`, `password`) VALUES
(6, 'ahmed', 'ahmed@mail.com', 'ahmed', '0578934764', '$2y$10$46/cykHZWUCwaxPwmaLQtuIYuz2cPpDBvg1mAZ6Dp4/Kxnd4dT2xy');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_username` (`student_username`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `exam_submissions`
--
ALTER TABLE `exam_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `student_username` (`student_username`);

--
-- Indexes for table `instructors`
--
ALTER TABLE `instructors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `exam_submissions`
--
ALTER TABLE `exam_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `instructors`
--
ALTER TABLE `instructors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `instructors` (`username`);

--
-- قيود الجداول `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD CONSTRAINT `assignment_submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`),
  ADD CONSTRAINT `assignment_submissions_ibfk_2` FOREIGN KEY (`student_username`) REFERENCES `users` (`username`);

--
-- قيود الجداول `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `instructors` (`email`);

--
-- قيود الجداول `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`);

--
-- قيود الجداول `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `instructors` (`email`);

--
-- قيود الجداول `exam_submissions`
--
ALTER TABLE `exam_submissions`
  ADD CONSTRAINT `exam_submissions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`),
  ADD CONSTRAINT `exam_submissions_ibfk_2` FOREIGN KEY (`student_username`) REFERENCES `users` (`username`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
