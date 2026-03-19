DROP DATABASE IF EXISTS student_course_hub;
CREATE DATABASE student_course_hub;
USE student_course_hub;

-- ---- Levels ----
CREATE TABLE Levels (
    LevelID   INTEGER PRIMARY KEY,
    LevelName TEXT NOT NULL
);

-- ---- Staff (extended) ----
CREATE TABLE Staff (
    StaffID    INTEGER PRIMARY KEY AUTO_INCREMENT,
    Name       TEXT    NOT NULL,
    Title      VARCHAR(120),          -- e.g. "Senior Lecturer"
    Bio        TEXT,                  -- rich staff profile
    Department VARCHAR(120)           -- e.g. "School of Computing"
);

-- ---- Modules ----
CREATE TABLE Modules (
    ModuleID        INTEGER PRIMARY KEY AUTO_INCREMENT,
    ModuleName      TEXT    NOT NULL,
    ModuleLeaderID  INTEGER,
    Description     TEXT,
    Image           TEXT,
    ImageAlt        VARCHAR(255),      -- WCAG: alt text for module image
    FOREIGN KEY (ModuleLeaderID) REFERENCES Staff(StaffID) ON DELETE SET NULL
);

-- ---- Programmes ----
CREATE TABLE Programmes (
    ProgrammeID       INTEGER PRIMARY KEY AUTO_INCREMENT,
    ProgrammeName     TEXT    NOT NULL,
    LevelID           INTEGER,
    ProgrammeLeaderID INTEGER,
    Description       TEXT,
    Image             TEXT,
    ImageAlt          VARCHAR(255),    -- WCAG: alt text for programme image
    Published         TINYINT(1) NOT NULL DEFAULT 1,  -- 0 = draft, 1 = live
    FOREIGN KEY (LevelID)            REFERENCES Levels(LevelID),
    FOREIGN KEY (ProgrammeLeaderID)  REFERENCES Staff(StaffID) ON DELETE SET NULL
);

-- ---- Programme ↔ Module junction ----
CREATE TABLE ProgrammeModules (
    ProgrammeModuleID INTEGER PRIMARY KEY AUTO_INCREMENT,
    ProgrammeID       INTEGER NOT NULL,
    ModuleID          INTEGER NOT NULL,
    Year              INTEGER NOT NULL,
    FOREIGN KEY (ProgrammeID) REFERENCES Programmes(ProgrammeID) ON DELETE CASCADE,
    FOREIGN KEY (ModuleID)    REFERENCES Modules(ModuleID)        ON DELETE CASCADE
);

-- ---- Interested Students (with duplicate guard + opt-out) ----
CREATE TABLE InterestedStudents (
    InterestID    INT AUTO_INCREMENT PRIMARY KEY,
    ProgrammeID   INT          NOT NULL,
    StudentName   VARCHAR(100) NOT NULL,
    Email         VARCHAR(255) NOT NULL,
    Active        TINYINT(1)   NOT NULL DEFAULT 1,  -- 0 = opted out
    RegisteredAt  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_programme_email (ProgrammeID, Email),           -- prevents duplicate sign-ups
    FOREIGN KEY (ProgrammeID) REFERENCES Programmes(ProgrammeID) ON DELETE CASCADE
);

-- ---- Admins (with role-based access) ----
CREATE TABLE Admins (
    AdminID      INT AUTO_INCREMENT PRIMARY KEY,
    Username     VARCHAR(60)  NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Role         ENUM('super_admin','editor','viewer') NOT NULL DEFAULT 'editor',
    CreatedAt    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---- Indexes for search performance ----
CREATE INDEX idx_programmes_level     ON Programmes(LevelID);
CREATE INDEX idx_programmes_published ON Programmes(Published);
CREATE INDEX idx_pm_programme         ON ProgrammeModules(ProgrammeID);
CREATE INDEX idx_pm_module            ON ProgrammeModules(ModuleID);
CREATE INDEX idx_interests_programme  ON InterestedStudents(ProgrammeID);
CREATE INDEX idx_interests_email      ON InterestedStudents(Email);

-- ============================================================
--  SEED DATA
-- ============================================================

INSERT INTO Levels (LevelID, LevelName) VALUES
(1, 'Undergraduate'),
(2, 'Postgraduate');

INSERT INTO Staff (StaffID, Name, Title, Department) VALUES
(1,  'Dr. Alice Johnson',    'Programme Leader',  'School of Computing'),
(2,  'Dr. Brian Lee',        'Senior Lecturer',   'School of Computing'),
(3,  'Dr. Carol White',      'Lecturer',          'School of Computing'),
(4,  'Dr. David Green',      'Associate Professor','School of Computing'),
(5,  'Dr. Emma Scott',       'Senior Lecturer',   'School of Computing'),
(6,  'Dr. Frank Moore',      'Lecturer',          'School of Computing'),
(7,  'Dr. Grace Adams',      'Programme Leader',  'Cyber Security Dept'),
(8,  'Dr. Henry Clark',      'Professor',         'AI Research Centre'),
(9,  'Dr. Irene Hall',       'Senior Lecturer',   'AI Research Centre'),
(10, 'Dr. James Wright',     'Lecturer',          'Cyber Security Dept'),
(11, 'Dr. Sophia Miller',    'Programme Leader',  'AI Research Centre'),
(12, 'Dr. Benjamin Carter',  'Senior Lecturer',   'Cyber Security Dept'),
(13, 'Dr. Chloe Thompson',   'Programme Leader',  'Data Science Dept'),
(14, 'Dr. Daniel Robinson',  'Programme Leader',  'AI Research Centre'),
(15, 'Dr. Emily Davis',      'Programme Leader',  'School of Computing'),
(16, 'Dr. Nathan Hughes',    'Lecturer',          'AI Research Centre'),
(17, 'Dr. Olivia Martin',    'Senior Lecturer',   'School of Computing'),
(18, 'Dr. Samuel Anderson',  'Associate Professor','Cyber Security Dept'),
(19, 'Dr. Victoria Hall',    'Professor',         'AI Research Centre'),
(20, 'Dr. William Scott',    'Lecturer',          'AI Research Centre');

INSERT INTO Modules (ModuleID, ModuleName, ModuleLeaderID, Description) VALUES
(1,  'Introduction to Programming',        1,  'Covers the fundamentals of programming using Python and Java.'),
(2,  'Mathematics for Computer Science',   2,  'Teaches discrete mathematics, linear algebra, and probability theory.'),
(3,  'Computer Systems & Architecture',    3,  'Explores CPU design, memory management, and assembly language.'),
(4,  'Databases',                          4,  'Covers SQL, relational database design, and NoSQL systems.'),
(5,  'Software Engineering',               5,  'Focuses on agile development, design patterns, and project management.'),
(6,  'Algorithms & Data Structures',       6,  'Examines sorting, searching, graphs, and complexity analysis.'),
(7,  'Cyber Security Fundamentals',        7,  'Provides an introduction to network security, cryptography, and vulnerabilities.'),
(8,  'Artificial Intelligence',            8,  'Introduces AI concepts such as neural networks, expert systems, and robotics.'),
(9,  'Machine Learning',                   9,  'Explores supervised and unsupervised learning, including decision trees and clustering.'),
(10, 'Ethical Hacking',                    10, 'Covers penetration testing, security assessments, and cybersecurity laws.'),
(11, 'Computer Networks',                  1,  'Teaches TCP/IP, network layers, and wireless communication.'),
(12, 'Software Testing & Quality Assurance',2, 'Focuses on automated testing, debugging, and code reliability.'),
(13, 'Embedded Systems',                   3,  'Examines microcontrollers, real-time OS, and IoT applications.'),
(14, 'Human-Computer Interaction',         4,  'Studies UI/UX design, usability testing, and accessibility.'),
(15, 'Blockchain Technologies',            5,  'Covers distributed ledgers, consensus mechanisms, and smart contracts.'),
(16, 'Cloud Computing',                    6,  'Introduces cloud services, virtualization, and distributed systems.'),
(17, 'Digital Forensics',                  7,  'Teaches forensic investigation techniques for cybercrime.'),
(18, 'Final Year Project',                 8,  'A major independent project where students develop a software solution.'),
(19, 'Advanced Machine Learning',          11, 'Covers deep learning, reinforcement learning, and cutting-edge AI techniques.'),
(20, 'Cyber Threat Intelligence',          12, 'Focuses on cybersecurity risk analysis, malware detection, and threat mitigation.'),
(21, 'Big Data Analytics',                 13, 'Explores data mining, distributed computing, and AI-driven insights.'),
(22, 'Cloud & Edge Computing',             14, 'Examines scalable cloud platforms, serverless computing, and edge networks.'),
(23, 'Blockchain & Cryptography',          15, 'Covers decentralized applications, consensus algorithms, and security measures.'),
(24, 'AI Ethics & Society',                16, 'Analyzes ethical dilemmas in AI, fairness, bias, and regulatory considerations.'),
(25, 'Quantum Computing',                  17, 'Introduces quantum algorithms, qubits, and cryptographic applications.'),
(26, 'Cybersecurity Law & Policy',         18, 'Explores digital privacy, GDPR, and international cyber law.'),
(27, 'Neural Networks & Deep Learning',    19, 'Delves into convolutional networks, GANs, and AI advancements.'),
(28, 'Human-AI Interaction',               20, 'Studies AI usability, NLP systems, and social robotics.'),
(29, 'Autonomous Systems',                 11, 'Focuses on self-driving technology, robotics, and intelligent agents.'),
(30, 'Digital Forensics & Incident Response',12,'Teaches forensic analysis, evidence gathering, and threat mitigation.'),
(31, 'Postgraduate Dissertation',          13, 'A major research project where students explore advanced topics in computing.');

INSERT INTO Programmes (ProgrammeName, LevelID, ProgrammeLeaderID, Description, Published) VALUES
('BSc Computer Science',      1, 1,  'A broad computer science degree covering programming, AI, cybersecurity, and software engineering.', 1),
('BSc Software Engineering',  1, 2,  'A specialized degree focusing on the development and lifecycle of software applications.', 1),
('BSc Artificial Intelligence',1,3,  'Focuses on machine learning, deep learning, and AI applications.', 1),
('BSc Cyber Security',        1, 4,  'Explores network security, ethical hacking, and digital forensics.', 1),
('BSc Data Science',          1, 5,  'Covers big data, machine learning, and statistical computing.', 1),
('MSc Machine Learning',      2, 11, 'A postgraduate degree focusing on deep learning, AI ethics, and neural networks.', 1),
('MSc Cyber Security',        2, 12, 'A specialized programme covering digital forensics, cyber threat intelligence, and security policy.', 1),
('MSc Data Science',          2, 13, 'Focuses on big data analytics, cloud computing, and AI-driven insights.', 1),
('MSc Artificial Intelligence',2,14, 'Explores autonomous systems, AI ethics, and deep learning technologies.', 1),
('MSc Software Engineering',  2, 15, 'Emphasizes software design, blockchain applications, and cutting-edge methodologies.', 1);

INSERT INTO ProgrammeModules (ProgrammeID, ModuleID, Year) VALUES
-- Shared Year 1 (All UG)
(1,1,1),(1,2,1),(1,3,1),(1,4,1),
(2,1,1),(2,2,1),(2,3,1),(2,4,1),
(3,1,1),(3,2,1),(3,3,1),(3,4,1),
(4,1,1),(4,2,1),(4,3,1),(4,4,1),
(5,1,1),(5,2,1),(5,3,1),(5,4,1),
-- Year 2
(1,5,2),(1,6,2),(1,7,2),(1,8,2),
(2,5,2),(2,6,2),(2,12,2),(2,14,2),
(3,5,2),(3,9,2),(3,8,2),(3,10,2),
(4,7,2),(4,10,2),(4,11,2),(4,17,2),
(5,5,2),(5,6,2),(5,9,2),(5,16,2),
-- Year 3
(1,11,3),(1,13,3),(1,15,3),(1,18,3),
(2,13,3),(2,15,3),(2,16,3),(2,18,3),
(3,13,3),(3,15,3),(3,16,3),(3,18,3),
(4,15,3),(4,16,3),(4,17,3),(4,18,3),
(5,9,3),(5,14,3),(5,16,3),(5,18,3),
-- Postgraduate
(6,19,1),(6,24,1),(6,27,1),(6,29,1),(6,31,1),
(7,20,1),(7,26,1),(7,30,1),(7,23,1),(7,31,1),
(8,21,1),(8,22,1),(8,27,1),(8,28,1),(8,31,1),
(9,19,1),(9,24,1),(9,28,1),(9,29,1),(9,31,1),
(10,23,1),(10,22,1),(10,25,1),(10,26,1),(10,31,1);

-- Default admin: username=admin, password=admin123
INSERT INTO Admins (Username, PasswordHash, Role) VALUES
('admin', '$2y$12$vDUYZFJaw.AiQPMNkLICL.lQIXGw1652iJb4Jca5cIGAGKjmedjUK', 'super_admin');

-- Sample interest registrations
INSERT INTO InterestedStudents (ProgrammeID, StudentName, Email) VALUES
(1, 'John Doe',    'john.doe@example.com'),
(4, 'Jane Smith',  'jane.smith@example.com'),
(6, 'Alex Brown',  'alex.brown@example.com'),
(9, 'Priya Patel', 'priya.patel@example.com');
