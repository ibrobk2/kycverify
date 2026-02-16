# NIN Verification Page - Setup Guide

## Overview
The NIN Verification page has been updated to provide a streamlined interface for retrieving NIN slips in three different verification methods.

## Features

### 1. **Verification Methods**
Three types of verification are available:
- **Verify by NIN** - Uses an 11-digit NIN number
- **Verify by Phone Number** - Uses an 11-digit phone number
- **Demographic Search** - Uses first name, last name, and date of birth

### 2. **Slip Types**
Users can select from four slip types for each verification method:
- **Premium** - Full detailed slip with comprehensive information
- **Regular** - Standard slip format
- **Standard** - Basic slip format
- **VNIN** - Virtual NIN slip

### 3. **API Endpoints**

#### NIN Verification Endpoints
- Premium: `https://dataverify.com.ng/developers/nin_slips/nin_premium.php`
- Regular: `https://dataverify.com.ng/developers/nin_slips/nin_regular.php`
- Standard: `https://dataverify.com.ng/developers/nin_slips/nin_standard.php`
- VNIN: `https://dataverify.com.ng/developers/nin_slips/vnin_slip.php`

#### Phone Number Verification Endpoints
- Premium: `https://dataverify.com.ng/developers/nin_slips/nin_premium_phone.php`
- Regular: `https://dataverify.com.ng/developers/nin_slips/nin_regular_phone.php`
- Standard: `https://dataverify.com.ng/developers/nin_slips/nin_standard_phone.php`

#### Demographic Search Endpoints
- Premium: `https://dataverify.com.ng/developers/nin_slips/nin_premium_demo.php`

**Note:** Demographic search only supports Premium slip type at this time.

## Setup Instructions

### 1. **Update API Key**
Open `nin-verification.php` and locate the API configuration section:

```javascript
// API Configuration
const API_KEY = "your_dataverify_api_key"; // Replace with your actual API key
```

Replace `"your_dataverify_api_key"` with your actual DataVerify API key.

### 2. **Ensure Database Table Exists**
For transaction logging, make sure the following table exists in your database:

```sql
CREATE TABLE IF NOT EXISTS `nin_verification_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `verification_type` VARCHAR(50) NOT NULL,
  `slip_type` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);
```

### 3. **API Request Format**
The page sends requests to DataVerify API with the following payload structure:

```json
{
  "api_key": "your_api_key",
  "verification_type": "nin|phone|demographic",
  "slip_type": "premium|regular|standard|vnin",
  "nin": "11_digit_nin",  // For NIN verification
  "phone": "11_digit_phone",  // For phone verification
  "first_name": "first_name",  // For demographic search
  "last_name": "last_name",  // For demographic search
  "dob": "YYYY-MM-DD"  // For demographic search
}
```

### 4. **API Response Handling**
The page expects the following response from DataVerify API:

```json
{
  "status": "success",
  "pdf_base64": "base64_encoded_pdf_string",
  "message": "Success message"
}
```

The page will:
- Decode the PDF from base64
- Trigger automatic download to user's device
- Log the transaction to the database
- Show success notification

## Form Flow

1. User selects verification type from dropdown
2. Slip type select becomes visible
3. User selects slip type
4. Relevant input fields appear based on verification type
5. User enters required information
6. Form validates input data
7. System checks wallet balance
8. On submit, calls DataVerify API with appropriate endpoint
9. PDF is decoded and downloaded automatically
10. Transaction is logged to database

## Error Handling

The form includes comprehensive error handling for:
- Invalid input validation
- Insufficient wallet balance
- Missing required fields
- API connection failures
- Invalid API responses

## Security Features

- Token-based authentication for transaction logging
- JWT verification for API requests
- Balance validation before processing
- Wallet integration to prevent unauthorized requests

## Customization

### To add new slip types:
1. Add endpoint to `ENDPOINTS` object in JavaScript
2. Add option to slip type select
3. Update API payload if needed

### To add new verification methods:
1. Add endpoint configuration to `ENDPOINTS` object
2. Add select option for verification type
3. Create new input section HTML
4. Add validation logic in `validateForm()` function
5. Update submit handler if payload differs

## Files Modified

- `nin-verification.php` - Main form and logic
- `api/log-nin-verification.php` - Transaction logging endpoint (new)

## Support

For issues or questions regarding the DataVerify API, visit: https://dataverify.com.ng/developers/
