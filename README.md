# Web-Based Doctor Appointment & Online Consultation Management System

![Project Preview](#) <!-- Add a project preview screenshot if desired -->

## Introduction
The advancement of web technologies has significantly transformed the healthcare sector. However, many healthcare facilities still rely on manual appointment systems, which cause long waiting times, scheduling conflicts, and inconvenience for patients. Moreover, people living in rural and remote areas in countries like Bangladesh often face difficulties in accessing qualified doctors due to distance and high travel costs.

This project proposes a web-based solution that enables efficient appointment booking and provides online consultation facilities. The system allows patients to book appointments instantly based on doctor availability and consult doctors via audio and video calls directly from their homes. This ensures faster, more reliable, and accessible healthcare services, especially for remote and rural patients.

## Purpose & Problem Statement
*   **The Problem:** Patients struggle with manual scheduling and delayed confirmations. Doctors and staff face challenges managing appointments efficiently. Furthermore, geographical barriers prevent rural populations from easily accessing quality medical professionals.
*   **The Solution:** An accessible digital platform to eliminate these hurdles. The system reduces wait times, streamlines schedule management, and leverages real-time calling to bridge the gap between patients and doctors, regardless of location.

## Scope & Core Features

### Patients
*   **Registration & Login:** Secure authentication process.
*   **Smart Doctor Search:** Find doctors effectively based on **location** or specialty.
*   **View Availability & Book:** Check real-time doctor schedules and request appointments.
*   **Slot Queue & Waiting Time:** Track queue position and estimated waiting time post-booking.
*   **Online Consultations:** Join real-time audio/video call sessions from the dashboard.
*   **Online Payments:** Securely pay consultation fees online.
*   **Medical Records:** View prescriptions and feedback reports post-consultation.

### Doctors
*   **Profile Management:** Update details and manage account settings.
*   **Schedule Management:** Define routine availability for patient appointments.
*   **Appointment Approvals:** Review, accept, or reject incoming appointment requests.
*   **Online Consultations:** Initiate and conduct real-time audio/video calls with scheduled patients.
*   **Reports & Feedback:** Upload prescriptions, diagnostic reports, and detailed feedback to patient dashboards.

### Administrators
*   **User Management:** Actively oversee and manage patient and doctor accounts, handling issues like blocked accounts.
*   **System Moderation:** Monitor ongoing appointments and overall system health.
*   **Payment Monitoring:** View and track all processed transaction histories to ensure financial integrity.

## Technology Stack
*   **Frontend:** HTML, CSS, JavaScript, Bootstrap *(Ensures a responsive, mobile-friendly UI)*
*   **Backend:** PHP *(Handles logic for routing, booking, approval, and consultations)*
*   **Database:** MySQL *(Maintains structured relational tables using PK/FK for entities like users, appointments, and payments)*
*   **Server Environment:** Apache (developed using XAMPP)

## Installation & Setup

1.  **Prerequisites:** 
    *   Install [XAMPP](https://www.apachefriends.org/) (or an equivalent Apache/MySQL server setup).
2.  **Clone the Repository:**
    *   Place the project folder into your server's appropriate root directory (`htdocs` for XAMPP).
3.  **Database Configuration:**
    *   Start Apache and MySQL via your XAMPP Control Panel.
    *   Open `db.php` in the project root and ensure it connects to your server.
4.  **Database Initialization:**
    *   Navigate to your browser and run `http://localhost/doctor_appointment/setup_db.php` to generate the MySQL tables and default data automatically.
5.  **Run the Application:**
    *   Access the home page at `http://localhost/doctor_appointment/` and start registering users.

## Meet the Team

| Name | ID | Primary Responsibilities |
| :--- | :--- | :--- |
| **Tasnim Akter** | 1060 | Requirement analysis, Testing, and Database Design |
| **Tabassum Sarker Sadia** | 1189 | Frontend Development, Testing, and UI Design |
| **Gulshan-ara Akter Dalia** | 1251 | Documentation, Testing, and Backend Development |

---
*Developed as a comprehensive solution for modernizing regional healthcare accessibility.*
