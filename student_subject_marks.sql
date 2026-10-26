CREATE TABLE student_subject_marks (
    id INT(11) NOT NULL AUTO_INCREMENT,
    student_id INT(11) NOT NULL,
    exam_id INT(11) NOT NULL,
    subject_id INT(11) NOT NULL,
    obtained_marks DECIMAL(6,2) NOT NULL,
    is_pass TINYINT(1) NOT NULL,
    grade VARCHAR(5) NOT NULL,
    gpa_point DECIMAL(3,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_student_exam_subject (student_id, exam_id, subject_id),
    KEY idx_exam_subject (exam_id, subject_id),
    KEY idx_subject (subject_id),
    CONSTRAINT fk_student_subject_marks_student
        FOREIGN KEY (student_id) REFERENCES students (id),
    CONSTRAINT fk_student_subject_marks_exam
        FOREIGN KEY (exam_id) REFERENCES exams (id),
    CONSTRAINT fk_student_subject_marks_subject
        FOREIGN KEY (subject_id) REFERENCES subjects (id),
    CONSTRAINT chk_student_subject_marks_is_pass CHECK (is_pass IN (0, 1)),
    CONSTRAINT chk_student_subject_marks_obtained CHECK (obtained_marks >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
