# MEDIQ — Healthcare Appointment, Serial & Live Queue Platform

> **Tagline**: “Book your doctor. Track your serial. Come when it's your turn.”  
> **Value Proposition**: “ডাক্তারের সিরিয়ালের জন্য ঘণ্টার পর ঘণ্টা অপেক্ষা নয়।”

---

## 1. Project Overview
MEDIQ is a mission-critical, production-ready healthcare coordination platform connecting patients with chamber doctors across Bangladesh to eliminate waiting-room congestion.

### Core Architectural Pillars
- **Single Mobile App (Flutter)**: One mobile application dynamically toggles between `PatientShell` and `DoctorShell` based on authenticated backend role claims.
- **Pure Web Standards**: Doctor Web and Admin Web utilize semantic HTML5, modern responsive CSS3, and Vanilla JavaScript ES6+ without frontend frameworks.
- **Clean Backend (PHP 8.2+ & MySQL 8+)**: Pure object-oriented layered architecture (Router, Controller, Validator, Service, Repository, PDO) with pessimistic locking (`SELECT ... FOR UPDATE`) and atomic token sequence counters.
- **Privacy & Security**: Zero public leakage of medical diagnoses or drugs; secure QR authenticity verification; comprehensive immutable audit logging.

---

## 2. Monorepo Structure

```text
mediq/
├── backend/            # PHP 8.2+ REST API, Services, Repositories, Database migrations
├── mobile_app/         # Single Flutter Mobile App (PatientShell & DoctorShell)
├── doctor_web/         # Doctor Web Portal (HTML5, CSS3, Vanilla JS ES6+, PHP)
├── admin_web/          # Admin Web Portal (HTML5, CSS3, Vanilla JS ES6+, PHP)
├── docs/               # In-depth architectural & technical specifications
│   ├── architecture.md # Layered architecture, mobile dynamic shell, risk mitigation
│   ├── database.md     # 38+ normalized tables, ERD, indexes, constraints
│   ├── queue-engine.md # State machines, concurrency algorithms, wait-time formulas
│   ├── api.md          # Complete REST API v1 catalog and error codes
│   ├── security.md     # RBAC matrix, IDOR prevention, healthcare privacy
│   ├── testing.md      # Concurrency race simulation tests & unit test matrix
│   └── deployment.md   # Linux production deployment, crons, backup/restore runbook
└── README.md           # Master project documentation
```

---

## 3. Getting Started

### Prerequisites
- PHP 8.2+ (with `pdo_mysql`, `mbstring`, `fileinfo`, `curl`, `json`)
- MySQL 8.0+ or MariaDB 10.4+
- Flutter SDK 3.x
- Web Server (Nginx / Apache or PHP Built-in Server for development)

### Quick Start (Development)
1. **Configure Backend**:
   ```bash
   cd backend
   cp .env.example .env
   # Edit .env with your MySQL credentials
   ```
2. **Review Architecture Docs**:
   Read [docs/architecture.md](docs/architecture.md), [docs/database.md](docs/database.md), and [docs/queue-engine.md](docs/queue-engine.md).
