# Isoftro-ERP

Nepal ERP is a comprehensive Enterprise Resource Planning system designed for educational institutions in Nepal. It streamlines administrative tasks, academic management, and student services.

## Features

- **Multi-Tenant Support**: Secure separation of data for different institutions.
- **Student Management**: Comprehensive student lifecycle management.
- **Academic Management**: Course, batch, and class management.
- **Attendance Tracking**: Daily attendance monitoring.
- **Fee Management**: Automated fee collection and tracking.
- **ID Card System**: Integrated ID card generation and management.
- **User Authentication**: Secure login system with role-based access control.

## Installation

1.  **Clone the repository**:
    ```bash
    git clone <repository-url>
    cd erp
    ```

2.  **Install Dependencies**:
    ```bash
    composer install
    ```

3.  **Database Setup**:
    - Create a database named `nepal_erp`.
    - Run the migration:
      ```bash
      php bin/console doctrine:migrations:migrate
      ```

4.  **Start Development Server**:
    ```bash
    php bin/console server:start
    ```

## Usage

- **Admin Login**: [http://localhost:8000/admin/login](http://localhost:8000/admin/login)
- **Student Login**: [http://localhost:8000/login](http://localhost:8000/login)

## License

Proprietary - All rights reserved.
