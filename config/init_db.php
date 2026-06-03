<?php
require_once 'db.php';

/** @var PDO $pdo */
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        username VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS guards (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        employee_id VARCHAR(100) UNIQUE NOT NULL,
        sex VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS teachers_staff (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        employee_id VARCHAR(100) UNIQUE NOT NULL,
        sex VARCHAR(20) NOT NULL,
        course VARCHAR(255),
        birthdate DATE,
        age INT,
        password VARCHAR(255) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        school_id VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        year_section VARCHAR(100),
        sex VARCHAR(20) NOT NULL,
        course VARCHAR(255),
        birthdate DATE,
        age INT,
        password VARCHAR(255) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS vehicles (
        id SERIAL PRIMARY KEY,
        owner_type VARCHAR(20) NOT NULL CHECK (owner_type IN ('Teacher/Staff', 'Student')),
        owner_id INT NOT NULL,
        type VARCHAR(20) NOT NULL CHECK (type IN ('Car', 'Motorcycle')),
        picture VARCHAR(255),
        registered_under VARCHAR(255) NOT NULL,
        color VARCHAR(100),
        brand VARCHAR(100),
        plate_number VARCHAR(100) UNIQUE NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Not Expired' CHECK (status IN ('Not Expired', 'Expired', 'Revoked')),
        date_registered DATE NOT NULL,
        date_expiration DATE NOT NULL,
        qr_code_path VARCHAR(255)
    )");

    echo "Database tables created successfully!";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
