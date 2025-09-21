# CBC Admin API Documentation

## Overview
This API provides endpoints for student and institution management, assessments, payments, and user-specific information for the CBC Admin educational platform.

## Base URL
```
http://your-domain.com/api
```

## Authentication
The API uses Laravel Sanctum for token-based authentication. Include the token in the Authorization header:

```
Authorization: Bearer {your_token}
```

## Endpoints

### Authentication

#### 1. User Registration (Student or Parent)
```http
POST /api/register
```

**Request Body:**
```json
{
    "name": "John Doe",
    "admission_number": "STU001",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "institution_id": 1,
    "grade_level": "Grade 8",
    "user_type": "student"
}
```

**Note:** 
- `user_type` is required and must be either "student" or "parent"
- `institution_id` and `grade_level` are optional fields. Students can register without being associated with an institution
- `phone_number` is required and must be a valid M-Pesa number with supported prefix
- For parent registration, `institution_id` and `grade_level` are typically not needed

**Response:**
```json
{
    "success": true,
    "message": "Student registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "admission_number": "STU001",
            "email": "john@example.com",
            "user_type": "student",
            "institution": {...}
        },
        "access_token": "1|abc123...",
        "token_type": "Bearer",
        "dashboard": {
            "token_balance": 0,
            "assessment_stats": {
                "total_attempts": 0,
                "completed_attempts": 0,
                "in_progress_attempts": 0,
                "average_score": 0,
                "total_tokens_used": 0,
                "completion_rate": 0
            },
            "recent_assessments": [],
            "recent_attempts": []
        }
    }
}
```

**Note:** The success message will be dynamic based on the `user_type`:
- For `user_type: "student"` → "Student registered successfully"
- For `user_type: "parent"` → "Parent registered successfully"

#### 2. Institution Registration
```http
POST /api/institution/register
```

**Request Body:**
```json
{
    "institution_name": "ABC School",
    "institution_email": "admin@abcschool.com",
    "institution_phone": "+254700000000",
    "institution_address": "123 Main St, Nairobi",
    "admin_name": "Admin User",
    "admin_email": "admin@abcschool.com",
    "admin_password": "password123",
    "admin_password_confirmation": "password123"
}
```


#### 3. Login
```http
POST /api/login
```

**Request Body:**
```json
{
    "login_identifier": "254700000000",
    "password": "password123"
}
```

**Note:** The `login_identifier` can be either:
- Phone number (for individual users and parents)
- Admission number (for institution students)

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "user_type": "student",
            "institution": {...},
            "wallet": {...}
        },
        "access_token": "1|abc123...",
        "token_type": "Bearer",
        "dashboard": {
            "token_balance": 150,
            "assessment_stats": {
                "total_attempts": 10,
                "completed_attempts": 8,
                "in_progress_attempts": 2,
                "average_score": 85.5,
                "total_tokens_used": 45,
                "completion_rate": 80.0
            },
            "recent_assessments": [...],
            "recent_attempts": [...]
        }
    }
}
```


#### 4. Logout
```http
POST /api/logout
```
*Requires authentication*

#### 5. Refresh Token
```http
POST /api/refresh
```
*Requires authentication*

### User Management

#### 6. Get Profile
```http
GET /api/profile
```
*Requires authentication*

#### 7. Update Profile
```http
PUT /api/profile
```
*Requires authentication*

**Request Body:**
```json
{
    "name": "Updated Name",
    "grade_level": "Grade 9"
}
```

#### 8. Update Password
```http
PUT /api/password
```
*Requires authentication*

**Request Body:**
```json
{
    "current_password": "oldpassword",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

### Dashboard & User Information

#### 9. Dashboard Overview
```http
GET /api/dashboard
```
*Requires authentication*

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {...},
        "token_balance": 150,
        "assessment_stats": {
            "total_attempts": 10,
            "completed_attempts": 8,
            "in_progress_attempts": 2,
            "average_score": 85.5,
            "total_tokens_used": 45,
            "completion_rate": 80.0
        },
        "recent_assessments": [...],
        "recent_attempts": [...]
    }
}
```

#### 10. Token Balance
```http
GET /api/token-balance
```
*Requires authentication*

#### 11. Assessment Statistics
```http
GET /api/assessment-stats
```
*Requires authentication*

#### 12. Recent Assessments
```http
GET /api/recent-assessments
```
*Requires authentication*

#### 13. Token History
```http
GET /api/token-history
```
*Requires authentication*

### Transactions

#### 14. My Transactions
```http
GET /api/my-transactions
```
*Requires authentication*

Returns comprehensive transaction history for the authenticated user including summary data and detailed transaction list.

**Query Parameters:**
- `per_page`: Items per page (default: 20)
- `page`: Page number (default: 1)

**Response:**
```json
{
    "success": true,
    "data": {
        "summary": {
            "total_spent": "1,250.00",
            "total_purchases": 5,
            "this_month_spent": "300.00",
            "total_tokens_credited": 500,
            "total_tokens_used": 150,
            "current_balance": 350
        },
        "transactions": {
            "current_page": 1,
            "data": [
                {
                    "id": "payment_123",
                    "type": "credit",
                    "transaction_type": "Token Purchase",
                    "date": "2024-01-15 14:30:00",
                    "amount": "100.00",
                    "tokens": 50,
                    "status": "successful",
                    "channel": "mpesa",
                    "reference": "QGH123456789",
                    "description": "Token purchase via mpesa",
                    "currency": "KES"
                },
                {
                    "id": "usage_456",
                    "type": "debit",
                    "transaction_type": "Assessment Attempt",
                    "date": "2024-01-14 10:15:00",
                    "amount": null,
                    "tokens": -10,
                    "status": "completed",
                    "assessment_title": "Mathematics Grade 6 - Term 1",
                    "assessment_grade": "Grade 6",
                    "assessment_subject": "Mathematics",
                    "score": 85.5,
                    "description": "Assessment attempt: Mathematics Grade 6 - Term 1",
                    "attempt_id": 456
                }
            ],
            "total": 25,
            "per_page": 20,
            "last_page": 2
        }
    }
}
```

#### 15. Institution Transactions (Institution users only)
```http
GET /api/institution/transactions
```
*Requires institution authentication*

Returns comprehensive transaction history for all students in the authenticated institution.

**Query Parameters:**
- `per_page`: Items per page (default: 20)
- `page`: Page number (default: 1)

**Response:**
```json
{
    "success": true,
    "data": {
        "summary": {
            "total_spent": "5,750.00",
            "total_purchases": 23,
            "this_month_spent": "1,200.00",
            "total_tokens_credited": 2300,
            "total_tokens_used": 850,
            "total_students": 45
        },
        "transactions": {
            "current_page": 1,
            "data": [
                {
                    "id": "payment_123",
                    "type": "credit",
                    "transaction_type": "Token Purchase",
                    "date": "2024-01-15 14:30:00",
                    "amount": "100.00",
                    "tokens": 50,
                    "status": "successful",
                    "channel": "mpesa",
                    "reference": "QGH123456789",
                    "description": "Token purchase via mpesa",
                    "currency": "KES",
                    "student_name": "John Doe",
                    "student_id": 123
                },
                {
                    "id": "usage_456",
                    "type": "debit",
                    "transaction_type": "Assessment Attempt",
                    "date": "2024-01-14 10:15:00",
                    "amount": null,
                    "tokens": -10,
                    "status": "completed",
                    "assessment_title": "Mathematics Grade 6 - Term 1",
                    "assessment_grade": "Grade 6",
                    "assessment_subject": "Mathematics",
                    "score": 85.5,
                    "description": "Assessment attempt: Mathematics Grade 6 - Term 1",
                    "attempt_id": 456,
                    "student_name": "Jane Smith",
                    "student_id": 124
                }
            ],
            "total": 150,
            "per_page": 20,
            "last_page": 8
        }
    }
}
```

### Assessments

#### 16. List All Assessments (for Practice)
```http
GET /api/assessments
```
*Requires authentication*

Returns all assessments that have questions, available for practice by any authenticated user.

**Query Parameters:**
- `search`: Search in title, description, or paper code
- `subject_id`: Filter by subject
- `grade_level`: Filter by grade level (e.g., "Grade 6", "Grade 9")
- `year`: Filter by year
- `exam_body`: Filter by exam body
- `sort_by`: Sort field (default: created_at)
- `sort_order`: Sort direction (asc/desc, default: desc)
- `per_page`: Items per page (default: 20)

**Response:**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "title": "Mathematics Grade 6 - Term 1",
                "description": "Comprehensive mathematics assessment",
                "grade_level": "Grade 6",
                "subject": {...},
                "creator": {...},
                "paper_code": "MATH6T1",
                "year": 2024,
                "exam_body": "KICD",
                "duration_minutes": 120,
                "questions_count": 50
            }
        ],
        "total": 25
    }
}
```

#### 17. Get Assessment Details
```http
GET /api/assessments/{id}
```
*Requires authentication*

Returns detailed information about a specific assessment including questions and sections.

#### 18. My Assessments (Student users only)
```http
GET /api/my-assessments
```
*Requires student authentication*

Returns assessments that the authenticated student has attempted.

**Query Parameters:**
- `search`: Search in title, description, or paper code
- `subject_id`: Filter by subject
- `grade_level`: Filter by grade level
- `year`: Filter by year
- `exam_body`: Filter by exam body
- `per_page`: Items per page (default: 20)

#### 19. Institution Assessments (Institution users only)
```http
GET /api/institution/assessments
```
*Requires institution authentication*

Returns assessments created by users from the authenticated institution.

**Query Parameters:**
- `search`: Search in title or description
- `subject_id`: Filter by subject
- `status`: Filter by status (active/archived)
- `per_page`: Items per page (default: 20)

### Payments

#### 17. List Payments
```http
GET /api/payments
```
*Requires authentication*

**Query Parameters:**
- `status`: Filter by status (pending, successful, failed, cancelled)
- `channel`: Filter by channel (mpesa, bank)
- `date_from`: Filter from date (YYYY-MM-DD)
- `date_to`: Filter to date (YYYY-MM-DD)
- `sort_by`: Sort field
- `sort_order`: Sort direction
- `per_page`: Items per page

#### 18. Create Payment
```http
POST /api/payments
```
*Requires authentication*

**Request Body:**
```json
{
    "amount": 1000.00,
    "channel": "mpesa",
    "currency": "KES",
    "tokens": 100,
    "phone_number": "+254700000000"
}
```

#### 19. Track Assessment Progress (Minute-by-Minute)
```http
POST /api/assessments/track-progress
```
*Requires authentication*

This endpoint is called every minute by the client during an active assessment to track progress and deduct tokens/minutes accordingly.

**Request Body:**
```json
{
    "attempt_id": 123,
    "minutes_elapsed": 1
}
```

**Response:**
```json
{
    "success": true,
    "message": "Progress tracked successfully",
    "data": {
        "attempt_id": 123,
        "minutes_elapsed": 1,
        "tokens_deducted": 0.5,
        "minutes_deducted": 1.0,
        "remaining_token_balance": 99.5,
        "remaining_minutes_balance": 99.0
    }
}
```

**Error Responses:**
- `400`: Insufficient balance
- `404`: Assessment attempt not found or not in progress
- `422`: Validation failed

#### 20. Get Payment Details
```http
GET /api/payments/{id}
```
*Requires authentication*

### Institution-Specific Endpoints

### Institution Student Management

#### 20. Get Institution Students
```http
GET /api/institution/students
```
*Requires institution authentication*

Returns all students for the authenticated institution with pagination and filtering options.

**Query Parameters:**
- `search`: Search in name, email, admission_number, or grade_level
- `grade_level`: Filter by grade level
- `per_page`: Items per page (default: 20)
- `page`: Page number (default: 1)

**Response:**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "John Doe",
                "admission_number": "STU001",
                "email": "john@example.com",
                "user_type": "student",
                "institution_id": 1,
                "grade_level": "Grade 6",
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z"
            }
        ],
        "total": 25,
        "per_page": 20,
        "last_page": 2
    }
}
```

#### 21. Create Single Student
```http
POST /api/institution/students
```
*Requires institution authentication*

**Request Body:**
```json
{
    "name": "John Doe",
    "admission_number": "STU001",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "grade_level": "Grade 6"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Student created successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "admission_number": "STU001",
        "email": "john@example.com",
        "user_type": "student",
        "institution_id": 1,
        "grade_level": "Grade 6",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

#### 22. Create Multiple Students
```http
POST /api/institution/students/multiple
```
*Requires institution authentication*

**Request Body:**
```json
{
    "students": [
        {
            "name": "John Doe",
            "admission_number": "STU001",
            "email": "john@example.com",
            "password": "password123",
            "grade_level": "Grade 6"
        },
        {
            "name": "Jane Smith",
            "admission_number": "STU002",
            "email": "jane@example.com",
            "password": "password123",
            "grade_level": "Grade 7"
        }
    ]
}
```

**Response:**
```json
{
    "success": true,
    "message": "2 students created successfully",
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "admission_number": "STU001",
            "email": "john@example.com",
            "user_type": "student",
            "institution_id": 1,
            "grade_level": "Grade 6",
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z"
        },
        {
            "id": 2,
            "name": "Jane Smith",
            "admission_number": "STU002",
            "email": "jane@example.com",
            "user_type": "student",
            "institution_id": 1,
            "grade_level": "Grade 7",
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z"
        }
    ]
}
```

#### 23. Import Students from Excel
```http
POST /api/institution/students/import
```
*Requires institution authentication*

**Note:** This endpoint requires Laravel Excel package to be installed. Install with: `composer require maatwebsite/excel`

**Request Body:**
- `file`: Excel file (xlsx, xls, csv) with student data

**Excel File Format:**
| name | admission_number | email | grade_level | password |
|------|------------------|-------|-------------|----------|
| John Doe | STU001 | john@example.com | Grade 6 | password123 |
| Jane Smith | STU002 | jane@example.com | Grade 7 | password123 |

**Response:**
```json
{
    "success": true,
    "message": "Students imported successfully",
    "data": {
        "imported_count": 2,
        "errors": [],
        "warnings": []
    }
}
```

#### 24. Get Specific Student
```http
GET /api/institution/students/{id}
```
*Requires institution authentication*

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "admission_number": "STU001",
        "email": "john@example.com",
        "user_type": "student",
        "institution_id": 1,
        "grade_level": "Grade 6",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

#### 25. Update Student
```http
PUT /api/institution/students/{id}
```
*Requires institution authentication*

**Request Body:**
```json
{
    "name": "John Doe Updated",
    "admission_number": "STU001",
    "email": "john.updated@example.com",
    "grade_level": "Grade 7"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Student updated successfully",
    "data": {
        "id": 1,
        "name": "John Doe Updated",
        "admission_number": "STU001",
        "email": "john.updated@example.com",
        "user_type": "student",
        "institution_id": 1,
        "grade_level": "Grade 7",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T12:00:00.000000Z"
    }
}
```

#### 26. Delete Student
```http
DELETE /api/institution/students/{id}
```
*Requires institution authentication*

**Response:**
```json
{
    "success": true,
    "message": "Student deleted successfully"
}
```

**Note:** Institution students do not have individual wallets. They share the institution admin's wallet for token management.

### Parent Learners Management

#### 27. Get Parent Learners
```http
GET /api/parent/learners
```
*Requires parent authentication*

Returns all learners for the authenticated parent.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "parent_id": 123,
            "name": "John Doe Jr.",
            "grade_level": "Grade 6",
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z"
        },
        {
            "id": 2,
            "parent_id": 123,
            "name": "Jane Doe",
            "grade_level": "Grade 4",
            "created_at": "2024-01-15T11:00:00.000000Z",
            "updated_at": "2024-01-15T11:00:00.000000Z"
        }
    ]
}
```

#### 28. Add Single Learner
```http
POST /api/parent/learners
```
*Requires parent authentication*

**Request Body:**
```json
{
    "name": "John Doe Jr.",
    "grade_level": "Grade 6"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Learner added successfully",
    "data": {
        "id": 1,
        "parent_id": 123,
        "name": "John Doe Jr.",
        "grade_level": "Grade 6",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

#### 29. Add Multiple Learners
```http
POST /api/parent/learners/multiple
```
*Requires parent authentication*

**Request Body:**
```json
{
    "learners": [
        {
            "name": "John Doe Jr.",
            "grade_level": "Grade 6"
        },
        {
            "name": "Jane Doe",
            "grade_level": "Grade 4"
        },
        {
            "name": "Bob Doe",
            "grade_level": "Grade 8"
        }
    ]
}
```

**Response:**
```json
{
    "success": true,
    "message": "3 learners added successfully",
    "data": [
        {
            "id": 1,
            "parent_id": 123,
            "name": "John Doe Jr.",
            "grade_level": "Grade 6",
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z"
        },
        {
            "id": 2,
            "parent_id": 123,
            "name": "Jane Doe",
            "grade_level": "Grade 4",
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z"
        },
        {
            "id": 3,
            "parent_id": 123,
            "name": "Bob Doe",
            "grade_level": "Grade 8",
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z"
        }
    ]
}
```

#### 30. Get Specific Learner
```http
GET /api/parent/learners/{id}
```
*Requires parent authentication*

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "parent_id": 123,
        "name": "John Doe Jr.",
        "grade_level": "Grade 6",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

#### 31. Update Learner
```http
PUT /api/parent/learners/{id}
```
*Requires parent authentication*

**Request Body:**
```json
{
    "name": "John Doe Jr. Updated",
    "grade_level": "Grade 7"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Learner updated successfully",
    "data": {
        "id": 1,
        "parent_id": 123,
        "name": "John Doe Jr. Updated",
        "grade_level": "Grade 7",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T12:00:00.000000Z"
    }
}
```

#### 32. Delete Learner
```http
DELETE /api/parent/learners/{id}
```
*Requires parent authentication*

**Response:**
```json
{
    "success": true,
    "message": "Learner deleted successfully"
}
```

### Admin Endpoints

#### 33. Get All Users (Admin only)
```http
GET /api/admin/users
```
*Requires admin authentication*

#### 34. Get All Institutions (Admin only)
```http
GET /api/admin/institutions
```
*Requires admin authentication*

## Error Responses

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

### Authentication Error (401)
```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

### Authorization Error (403)
```json
{
    "success": false,
    "message": "Access denied to this assessment"
}
```

### Server Error (500)
```json
{
    "success": false,
    "message": "Registration failed",
    "error": "Database connection error"
}
```

## User Types & Permissions

### Student Users
- Can register and login
- Can view their profile and update it
- Can view assessments they have access to
- Can view their assessment attempts and scores
- Can make payments to purchase tokens
- Can view their token balance and history

### Parent Users
- Can register and login
- Can view their profile and update it
- Can add, view, update, and delete their learners
- Can add multiple learners at once
- Can view their token balance and history
- Can make payments to purchase tokens

### Institution Users
- Can register their institution
- Can view and manage students in their institution
- Can view assessments created by their institution
- Can view student performance data
- Can make payments for their institution

### Admin Users
- Have access to all endpoints
- Can view all users and institutions
- Can manage system-wide settings

## Token and Minutes Management

### New Minutes-Based System
The system has been updated to use a minutes-based tracking system instead of the previous tokens-per-assessment model:

1. **Deposits**: When users make payments, both tokens and minutes are credited to their wallet
2. **Assessment Start**: Users pay 1 token and 1 minute to start an assessment
3. **Minute-by-Minute Tracking**: During assessment, tokens and minutes are deducted every minute based on the `minutes_per_token` setting
4. **Balance Tracking**: Users can track both their token balance and available minutes

### Token and Minutes Lifecycle
1. **Payment**: User makes payment → tokens and minutes credited to wallet
2. **Assessment Start**: 1 token + 1 minute deducted to begin assessment
3. **Progress Tracking**: Every minute, fractional tokens and 1 minute deducted
4. **Assessment End**: No additional charges when assessment is completed

### Settings
- `minutes_per_token`: Defines how many minutes each token provides (default: 1.0)
- This setting determines the fractional token deduction per minute

### API Token Management

### API Token Lifecycle
1. **Registration/Login**: User receives an access token
2. **API Requests**: Include token in Authorization header
3. **Token Refresh**: Use refresh endpoint to get new token
4. **Logout**: Token is invalidated

### API Token Security
- Tokens are automatically invalidated on logout
- Tokens can be refreshed to extend session
- Each user can have only one active token at a time

## Rate Limiting
The API implements rate limiting to prevent abuse. Respect the rate limits and implement appropriate retry logic in your client applications.

## Best Practices

### Client Implementation
1. Store tokens securely (not in localStorage for web apps)
2. Implement automatic token refresh before expiration
3. Handle authentication errors gracefully
4. Implement retry logic for failed requests
5. Cache user data when appropriate

### Error Handling
1. Always check the `success` field in responses
2. Handle different HTTP status codes appropriately
3. Display user-friendly error messages
4. Log errors for debugging

### Security
1. Never expose tokens in client-side code
2. Use HTTPS in production
3. Implement proper input validation
4. Sanitize user inputs before sending to API

## Support
For API support and questions, please contact the development team or refer to the platform documentation.
