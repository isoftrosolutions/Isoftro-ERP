<?php
        return [
            'student_registration_success' => [
                'subject' => 'Registration Successful - {{institute_name}}! 🎓',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Registeration successful</h2>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello , <strong style="color:#111;">{{student_name}} ji</strong></p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">You are successfully enrolled in the batch for <span style="color:#2d3436;font-weight:600;">{{course_name}}</span> .</p>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-top:0;margin-bottom:16px;">Login details</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>portal :-</strong> <a href="{{login_url}}" style="color:#009E7E;text-decoration:none;">{{login_url}}</a></p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Username :-</strong> {{student_email}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>password :-</strong> {{temp_password}}</p>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Best wishes</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'student_account_verification' => [
                'subject' => 'Verify Your Email - {{institute_name}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Verify Your Email</h2>
                        <p style="color:#636e72;margin-top:8px;">One last step to complete your registration.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">Welcome to {{institute_name}}! To access your student portal and start your journey, please verify your email address by clicking the button below.</p>
                    
                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{verification_link}}" style="display:inline-block;padding:16px 36px;background:#009E7E;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(0,158,126,0.2);">Verify My Email</a>
                    </div>

                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-top:0;margin-bottom:16px;">Account Preview</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Roll Number :-</strong> {{roll_no}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Email :-</strong> {{student_email}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Course :-</strong> {{course_name}}</p>
                    </div>

                    <p style="font-size:14px;color:#636e72;margin-bottom:32px;text-align:center;">Link expires in 24 hours. If you didn\'t create this account, please ignore this email.</p>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Best wishes,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'student_profile_updated' => [
                'subject' => 'Profile Updated Successfully - {{institute_name}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Profile Updated</h2>
                        <p style="color:#636e72;margin-top:8px;">Your changes have been saved successfully.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">This is to confirm that your profile information was updated on {{current_date}}. Please review the details below.</p>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-top:0;margin-bottom:16px;">Updated details</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Name :-</strong> {{student_name}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Roll No :-</strong> {{roll_no}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Phone :-</strong> {{phone}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Email :-</strong> {{student_email}}</p>
                    </div>

                    <p style="font-size:14px;color:#636e72;margin-bottom:32px;">If you did not make these changes, please contact the administration office immediately at {{institute_phone}}.</p>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Best wishes,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'password_reset_request' => [
                'subject' => 'Password Reset Request - {{institute_name}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Password Reset</h2>
                        <p style="color:#636e72;margin-top:8px;">We received a request to reset your password.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">Use the verification code below to proceed with the reset process. This code is valid for <strong>30 minutes</strong>.</p>
                    
                    <div style="text-align:center;margin:32px 0;">
                        <div style="display:inline-block;border:2px dashed #009E7E;border-radius:16px;padding:20px 40px;background:#f0faf5;">
                            <span style="font-size:36px;font-weight:900;letter-spacing:8px;color:#009e7e;">{{reset_token}}</span>
                        </div>
                    </div>

                    <p style="text-align:center;font-size:14px;color:#636e72;margin-bottom:20px;">Alternatively, you can click the button below:</p>
                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{reset_link}}" style="display:inline-block;padding:16px 36px;background:#009E7E;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(0,158,126,0.2);">Reset Password Now</a>
                    </div>

                    <p style="font-size:12px;color:#94a3b8;line-height:1.5;text-align:center;">If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Security Team,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],

            'password_changed_success' => [
                'subject' => 'Password Changed Successfully - {{institute_name}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Password Changed</h2>
                        <p style="color:#636e72;margin-top:8px;">Your account security has been updated.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">Your password has been changed successfully on {{current_date}}. If you made this change, no further action is required.</p>
                    
                    <div style="background:#fff9f9;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #ffecec;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#e74c3c;margin-top:0;margin-bottom:16px;">⚠️ Security Alert</h3>
                        <p style="margin:8px 0;font-size:14px;line-height:1.5;">If you did <strong>NOT</strong> change your password, please reset it immediately or contact our security team.</p>
                        <p style="margin:16px 0 0;font-size:14px;"><strong>Contact :-</strong> {{institute_phone}}</p>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Best wishes,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'payment_success_full' => [
                'subject' => 'Payment Receipt #{{receipt_no}} - {{institute_name}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Payment Successful</h2>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">Your payment for <span style="color:#2d3436;font-weight:600;">{{course_name}}</span> has been received successfully. Below are your receipt details.</p>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <table style="width:100%;border-collapse:collapse;font-size:14px;">
                            <tr><td style="padding:4px 0;color:#94a3b8;width:50%;">Receipt No:</td><td style="padding:4px 0;color:#2d3436;font-weight:700;text-align:right;">{{receipt_no}}</td></tr>
                            <tr><td style="padding:4px 0;color:#94a3b8;">Paid Date:</td><td style="padding:4px 0;color:#2d3436;font-weight:700;text-align:right;">{{paid_date}}</td></tr>
                            <tr><td colspan="2" style="border-top:1px solid #e2e8f0;margin:8px 0;padding:8px 0;"></td></tr>
                            <tr><td style="padding:4px 0;color:#94a3b8;">Course Fee:</td><td style="padding:4px 0;color:#2d3436;font-weight:700;text-align:right;">Rs. {{course_fee}}</td></tr>
                            <tr><td style="padding:4px 0;color:#009E7E;font-weight:600;">Paid Today:</td><td style="padding:4px 0;color:#009E7E;font-weight:800;text-align:right;">Rs. {{amount}}</td></tr>
                            <tr><td style="padding:4px 0;color:#94a3b8;">Previous Payments:</td><td style="padding:4px 0;color:#2d3436;font-weight:700;text-align:right;">Rs. {{previous_payments}}</td></tr>
                            <tr><td colspan="2" style="border-top:1px solid #e2e8f0;margin:8px 0;padding:8px 0;"></td></tr>
                            <tr style="font-size:16px;"><td style="padding:4px 0;color:#111;font-weight:800;">BALANCE DUE:</td><td style="padding:4px 0;color:#111;font-weight:900;text-align:right;">Rs. {{balance}}</td></tr>
                        </table>
                    </div>

                    <div style="text-align:center;margin-top:20px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:12px 24px;background:#009E7E;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:700;">View in Student Portal</a>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Best wishes,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'payment_success_partial' => [
                'subject' => 'Partial Payment Received - Receipt #{{receipt_no}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Partial Payment Received</h2>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">Your partial payment for <span style="color:#2d3436;font-weight:600;">{{course_name}}</span> has been confirmed. Below are the updated balance details.</p>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <table style="width:100%;border-collapse:collapse;font-size:14px;">
                            <tr><td style="padding:4px 0;color:#94a3b8;width:50%;">Receipt No:</td><td style="padding:4px 0;color:#2d3436;font-weight:700;text-align:right;">{{receipt_no}}</td></tr>
                            <tr><td style="padding:4px 0;color:#94a3b8;">Paid Date:</td><td style="padding:4px 0;color:#2d3436;font-weight:700;text-align:right;">{{paid_date}}</td></tr>
                            <tr><td colspan="2" style="border-top:1px solid #e2e8f0;margin:8px 0;padding:8px 0;"></td></tr>
                            <tr><td style="padding:4px 0;color:#94a3b8;">Course Fee:</td><td style="padding:4px 0;color:#2d3436;font-weight:700;text-align:right;">Rs. {{course_fee}}</td></tr>
                            <tr><td style="padding:4px 0;color:#009E7E;font-weight:600;">Paid Today:</td><td style="padding:4px 0;color:#009E7E;font-weight:800;text-align:right;">Rs. {{amount}}</td></tr>
                            <tr><td style="padding:4px 0;color:#94a3b8;">Previous Payments:</td><td style="padding:4px 0;color:#2d3436;font-weight:700;text-align:right;">Rs. {{previous_payments}}</td></tr>
                            <tr><td colspan="2" style="border-top:1px solid #e2e8f0;margin:8px 0;padding:8px 0;"></td></tr>
                            <tr style="font-size:16px;"><td style="padding:4px 0;color:#e67e22;font-weight:800;">REMAINING BALANCE:</td><td style="padding:4px 0;color:#e67e22;font-weight:900;text-align:right;">Rs. {{balance}}</td></tr>
                        </table>
                    </div>

                    <div style="text-align:center;margin-top:20px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:12px 24px;background:#009E7E;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:700;">Pay Remaining Balance</a>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Best wishes,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'payment_failed' => [
                'subject' => 'Payment Failed - Action Required',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#e74c3c;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Payment Failed</h2>
                        <p style="color:#636e72;margin-top:8px;">Your recent transaction could not be processed.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">We\'re sorry, but your recent payment attempt was unsuccessful. Please review the details below and try again.</p>
                    
                    <div style="background:#fff9f9;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #ffecec;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#e74c3c;margin-top:0;margin-bottom:16px;">Transaction details</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Date :-</strong> {{current_date}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Amount :-</strong> NPR {{amount}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Method :-</strong> {{payment_mode}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Reference :-</strong> {{transaction_id}}</p>
                    </div>

                    <p style="text-align:center;font-size:14px;color:#636e72;margin-bottom:20px;">You can retry the payment by clicking the button below:</p>
                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#e74c3c;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(231,76,60,0.2);">Retry Payment</a>
                    </div>

                    <p style="font-size:14px;color:#636e72;margin-bottom:32px;text-align:center;">Need help? Contact our accounts team at {{institute_phone}}.</p>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Accounts Department,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'payment_pending' => [
                'subject' => 'Payment Under Verification - {{institute_name}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#f39c12;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Payment Pending</h2>
                        <p style="color:#636e72;margin-top:8px;">We are currently verifying your payment.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">We have received your payment submission and it is currently under manual verification. This process usually takes 1-2 business days.</p>
                    
                    <div style="background:#fcfaf2;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f9f1d8;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#d68910;margin-top:0;margin-bottom:16px;">Transaction review</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Reference :-</strong> {{transaction_id}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Amount :-</strong> NPR {{amount}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Method :-</strong> {{payment_mode}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Status :-</strong> PENDING VERIFICATION</p>
                    </div>

                    <p style="font-size:14px;color:#636e72;margin-bottom:32px;text-align:center;">You will receive an official receipt once the verification is complete.</p>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Accounts Department,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'fee_reminder_7days' => [
                'subject' => 'Fee Payment Reminder - Due in 7 Days',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009e7e;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Upcoming Payment</h2>
                        <p style="color:#636e72;margin-top:8px;">Friendly reminder: Your fee is due in 7 days.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">This is a friendly reminder regarding your upcoming fee payment. Please ensure timely payment to avoid any late fines.</p>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-top:0;margin-bottom:16px;">Payment Summary</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Balance Due :-</strong> NPR {{balance}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Due Date :-</strong> {{due_date}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Installment :-</strong> {{installment_no}}</p>
                    </div>

                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#009E7E;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(0,158,126,0.2);">Pay via Portal</a>
                    </div>

                    <p style="font-size:14px;color:#636e72;margin-bottom:32px;text-align:center;">Already paid? Please ignore this message or upload your proof on the portal.</p>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Accounts Team,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'fee_reminder_3days' => [
                'subject' => 'URGENT: Fee Payment Due in 3 Days ⚠️',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#e67e22;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Urgent: Payment Due</h2>
                        <p style="color:#636e72;margin-top:8px;">Action required: Your fee is due in 3 days.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">This is an urgent reminder that your outstanding balance is due in 3 days. Please settle it soon to avoid late payment penalties.</p>
                    
                    <div style="background:#fffaf4;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #fdf2e9;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#e67e22;margin-top:0;margin-bottom:16px;">Outstanding Amount</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Balance Due :-</strong> NPR {{balance}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Due Date :-</strong> {{due_date}} ⏰</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Course :-</strong> {{course_name}}</p>
                    </div>

                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#e67e22;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(230,126,34,0.2);">Pay Immediately</a>
                    </div>

                    <p style="font-size:14px;color:#636e72;margin-bottom:32px;text-align:center;">Need more time? Please contact the accounts office at {{institute_phone}}.</p>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Accounts Team,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'fee_overdue_notice' => [
                'subject' => 'OVERDUE: Payment Required Immediately 🔴',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#e74c3c;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Payment Overdue</h2>
                        <p style="color:#636e72;margin-top:8px;">Immediate action required: Your payment is past due.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">Your fee payment deadline has passed. To avoid further accumulation of fines and potential restriction of services, please clear your balance immediately.</p>
                    
                    <div style="background:#fff9f9;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #ffecec;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#e74c3c;margin-top:0;margin-bottom:16px;">Overdue Balance</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Pending Amount :-</strong> NPR {{balance}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Late Fine :-</strong> NPR {{fine_applied}}</p>
                        <p style="margin:8px 0;font-size:14px;color:#e74c3c;font-weight:700;"><strong>Total Payable :-</strong> NPR {{total_payable}}</p>
                    </div>

                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#e74c3c;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(231,76,60,0.2);">Clear Dues Now</a>
                    </div>

                    <p style="font-size:14px;color:#636e72;margin-bottom:32px;text-align:center;">Facing financial difficulty? Meet with the accounts desk within 24 hours to discuss options.</p>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Accounts Department,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'invoice_generated' => [
                'subject' => 'New Invoice Generated - {{invoice_number}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Invoice Generated</h2>
                        <p style="color:#636e72;margin-top:8px;">A new fee invoice is ready for review.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">A new fee invoice has been generated for your recent installment. You can download the official document and pay online via the student portal.</p>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-top:0;margin-bottom:16px;">Invoice Summary</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Invoice No :-</strong> {{invoice_number}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Amount :-</strong> NPR {{total_amount}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Issue Date :-</strong> {{invoice_date}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Due Date :-</strong> {{due_date}}</p>
                    </div>

                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#009E7E;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(0,158,126,0.2);">View & Pay Invoice</a>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Accounts Department,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'payment_refund_processed' => [
                'subject' => 'Refund Processed - {{receipt_no}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Refund Processed</h2>
                        <p style="color:#636e72;margin-top:8px;">Your refund has been approved and completed.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">Good news! Your refund request for receipt #{{receipt_no}} has been processed. Please see the details below.</p>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-top:0;margin-bottom:16px;">Refund details</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Amount :-</strong> NPR {{amount}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Refund Date :-</strong> {{refund_date}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Method :-</strong> {{refund_method}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Transaction :-</strong> {{transaction_id}}</p>
                    </div>

                    <p style="font-size:14px;color:#636e72;margin-bottom:32px;text-align:center;">Depending on your bank, it may take 3-5 business days for the amount to reflect in your account.</p>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Accounts Department,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'course_enrollment_success' => [
                'subject' => 'Successfully Enrolled - {{course_name}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Enrollment Confirmed</h2>
                        <p style="color:#636e72;margin-top:8px;">You are now officially enrolled in {{course_name}}.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">Congratulations! Your enrollment in the upcoming batch has been confirmed. We are excited to have you join us. Here are your course details.</p>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-top:0;margin-bottom:16px;">Course Information</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Course :-</strong> {{course_name}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Batch :-</strong> {{batch_name}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Start Date :-</strong> {{batch_start_date}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Roll Number :-</strong> {{roll_no}}</p>
                    </div>

                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#009E7E;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(0,158,126,0.2);">Access Student Portal</a>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Academic Department,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'exam_schedule_published' => [
                'subject' => 'Exam Schedule Announced - {{exam_name}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009e7e;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Exam Schedule</h2>
                        <p style="color:#636e72;margin-top:8px;">The schedule for <strong>{{exam_name}}</strong> is now available.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">The academic department has finalized the schedule for your upcoming examinations. Please prepare accordingly.</p>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-top:0;margin-bottom:16px;">Exam Details</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Exam Name :-</strong> {{exam_name}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Date :-</strong> {{exam_date}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Time :-</strong> {{exam_time}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Venue :-</strong> {{exam_venue}}</p>
                    </div>

                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#009E7E;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(0,158,126,0.2);">Download Admit Card</a>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Examination Dept,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'exam_results_published' => [
                'subject' => 'Exam Results Available - {{exam_name}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Results Published</h2>
                        <p style="color:#636e72;margin-top:8px;">Your performance for {{exam_name}} is now live.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">The results for your recent examination have been published. Here is a quick summary of your performance.</p>
                    
                    <div style="background:#f0faf5;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #dff2e8;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#009E7E;margin-top:0;margin-bottom:16px;">Result Summary</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Marks :-</strong> {{marks_obtained}} / {{total_marks}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Percentage :-</strong> {{percentage}}%</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Grade :-</strong> {{grade}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Status :-</strong> <span style="color:#009E7E;font-weight:700;">{{result_status}}</span></p>
                    </div>

                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#009E7E;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(0,158,126,0.2);">View Detailed Marksheet</a>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Examination Dept,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'assignment_new' => [
                'subject' => 'New Assignment: {{assignment_title}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#004D30;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">New Assignment</h2>
                        <p style="color:#636e72;margin-top:8px;">A new task has been assigned for <strong>{{course_name}}</strong>.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">Your instructor has posted a new assignment. Please review the requirements and ensure timely submission.</p>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-top:0;margin-bottom:16px;">Task details</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Title :-</strong> {{assignment_title}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Subject :-</strong> {{subject_name}}</p>
                        <p style="margin:8px 0;font-size:14px;color:#e74c3c;"><strong>Due Date :-</strong> {{assignment_due_date}}</p>
                    </div>

                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#004D30;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(0,77,48,0.2);">View Assignment</a>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Academic Department,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'assignment_submission_confirmed' => [
                'subject' => 'Assignment Submitted Successfully - {{assignment_title}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Submission Received</h2>
                        <p style="color:#636e72;margin-top:8px;">Your assignment has been uploaded successfully.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">This is to confirm that we have received your assignment submission. Your instructor will evaluate it shortly.</p>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-top:0;margin-bottom:16px;">Submission Summary</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Assignment :-</strong> {{assignment_title}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Submitted On :-</strong> {{submitted_at}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>File :-</strong> {{submission_filename}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Status :-</strong> <span style="color:#009E7E;font-weight:700;">{{submission_status}}</span></p>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Academic Department,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'assignment_graded' => [
                'subject' => 'Assignment Evaluated - {{assignment_title}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Assignment Graded</h2>
                        <p style="color:#636e72;margin-top:8px;">Evaluation for <strong>{{assignment_title}}</strong> is complete.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">Your instructor has finished grading your submission. Here is a summary of your results and feedback.</p>
                    
                    <div style="background:#f0faf5;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #dff2e8;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#009E7E;margin-top:0;margin-bottom:16px;">Grading summary</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Marks :-</strong> {{marks_awarded}} / {{max_marks}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Grade :-</strong> {{assignment_grade}}</p>
                        <p style="margin:16px 0 8px;font-size:13px;color:#636e72;line-height:1.5;"><strong>Feedback :-</strong> {{feedback}}</p>
                    </div>

                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#009E7E;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(0,158,126,0.2);">View Full Feedback</a>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Academic Department,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'attendance_warning' => [
                'subject' => 'ATTENTION REQUIRED: Low Attendance Alert ⚠️',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#e74c3c;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Attendance Warning</h2>
                        <p style="color:#636e72;margin-top:8px;">Action required: Your attendance is below the minimum threshold.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">Our records indicate that your attendance for <strong>{{course_name}}</strong> has fallen below the required minimum. This may impact your eligibility for final examinations.</p>
                    
                    <div style="background:#fff9f9;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #ffecec;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#e74c3c;margin-top:0;margin-bottom:16px;">Attendance Summary</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Current Stat :-</strong> <span style="color:#e74c3c;font-weight:700;">{{attendance_percentage}}%</span></p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Required :-</strong> {{required_attendance}}%</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Missed Classes :-</strong> {{classes_missed}}</p>
                    </div>

                    <p style="text-align:center;font-size:14px;color:#636e72;margin-bottom:20px;">Please meet with your academic advisor or review your history on the portal:</p>
                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#e74c3c;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(231,76,60,0.2);">Review Attendance</a>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Academic Department,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'general_announcement' => [
                'subject' => 'Important Announcement - {{institute_name}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Announcement</h2>
                        <p style="color:#636e72;margin-top:8px;">{{announcement_title}}</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <div style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#2d3436;">
                        {{announcement_content}}
                    </div>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <p style="margin:0;font-size:14px;"><strong>Date :-</strong> {{announcement_date}}</p>
                        <p style="margin:8px 0 0;font-size:14px;"><strong>Priority :-</strong> {{priority}}</p>
                    </div>

                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#009E7E;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(0,158,126,0.2);">View on Portal</a>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Administration,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'account_suspended' => [
                'subject' => 'Account Suspension Notice - {{institute_name}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#e74c3c;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Account Suspended</h2>
                        <p style="color:#636e72;margin-top:8px;">Access to your student portal has been restricted.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">This is to inform you that your student account at {{institute_name}} has been temporarily suspended due to administrative reasons.</p>
                    
                    <div style="background:#fff9f9;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #ffecec;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#e74c3c;margin-top:0;margin-bottom:16px;">Suspension details</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Reason :-</strong> {{suspension_reason}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Date :-</strong> {{suspension_date}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Roll No :-</strong> {{roll_no}}</p>
                    </div>

                    <p style="font-size:14px;color:#636e72;margin-bottom:32px;line-height:1.5;">To restore your account access, please follow the instructions below or visit the administration office.</p>
                    
                    <div style="background:#f8fafc;padding:20px;border-radius:12px;margin-bottom:32px;border:1px solid #f1f5f9;font-size:13px;color:#2d3436;">
                        <strong>Instructions:</strong> {{restoration_instructions}}
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Administration,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'account_reactivated' => [
                'subject' => 'Account Reactivated - Welcome Back!',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Account Reactivated</h2>
                        <p style="color:#636e72;margin-top:8px;">Welcome back! Your access has been restored.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">Good news! Your student account has been reactivated. You now have full access to all institute services and the student portal.</p>
                    
                    <div style="background:#f0faf5;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #dff2e8;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#009E7E;margin-top:0;margin-bottom:16px;">Account details</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Status :-</strong> <span style="color:#009E7E;font-weight:700;">ACTIVE</span></p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Reactivated On :-</strong> {{reactivation_date}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Email :-</strong> {{student_email}}</p>
                    </div>

                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#009E7E;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(0,158,126,0.2);">Back to Portal</a>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Administration,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'document_verification_required' => [
                'subject' => 'Document Verification Required - {{institute_name}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#f39c12;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Verification Required</h2>
                        <p style="color:#636e72;margin-top:8px;">Action required: Please provide the missing documents.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">We are currently verifying your student profile and noticed some documents are missing or require updated versions.</p>
                    
                    <div style="background:#fcfaf2;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f9f1d8;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#d68910;margin-top:0;margin-bottom:16px;">Required documents</h3>
                        <div style="font-size:14px;color:#2d3436;line-height:1.6;">
                            {{document_list}}
                        </div>
                        <p style="margin:16px 0 0;font-size:13px;color:#e67e22;"><strong>Deadline :-</strong> {{submission_deadline}}</p>
                    </div>

                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#f39c12;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(243,156,18,0.2);">Upload Documents</a>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Administration,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'leave_request_status' => [
                'subject' => 'Leave Request {{status}} - {{institute_name}}',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009e7e;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Leave Request</h2>
                        <p style="color:#636e72;margin-top:8px;">Your application has been reviewed.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{student_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">This is to inform you that your leave request has been processed. Please see the status and remarks below.</p>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-top:0;margin-bottom:16px;">Request summary</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Status :-</strong> <span style="font-weight:700;">{{leave_status}}</span></p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Duration :-</strong> {{from_date}} to {{to_date}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Remarks :-</strong> {{leave_remarks}}</p>
                    </div>

                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:16px 36px;background:#009E7E;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 4px 15px rgba(0,158,126,0.2);">View History</a>
                    </div>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Administration,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'staff_welcome' => [
                'subject' => 'Welcome to the Team! - {{institute_name}} 🏢',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009E7E;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.5px;">Welcome to the Team!</h2>
                        <p style="color:#636e72;margin-top:8px;">Your account as <strong>{{role_label}}</strong> has been created.</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{staff_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">Welcome aboard! We are excited to have you with us. You can now log into the portal using the credentials below to manage your dashboard and activities.</p>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:16px;margin-bottom:32px;border:1px solid #f1f5f9;">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-top:0;margin-bottom:16px;">Login details</h3>
                        <p style="margin:8px 0;font-size:14px;"><strong>Portal :-</strong> <a href="{{login_url}}" style="color:#009E7E;text-decoration:none;">{{login_url}}</a></p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Email :-</strong> {{staff_email}}</p>
                        <p style="margin:8px 0;font-size:14px;"><strong>Password :-</strong> {{temp_password}}</p>
                    </div>

                    <p style="font-size:14px;color:#636e72;margin-bottom:32px;">For security reasons, we recommend changing your password after your first login.</p>

                    <div style="text-align:right;margin-top:40px;">
                        <p style="margin:0;font-size:16px;color:#2d3436;">Best regards,</p>
                        <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#009E7E;">{{institute_name}}</p>
                    </div>
                </div>'
            ],
            'tenant_welcome' => [
                'subject' => 'Welcome to Isoftro ERP! Your Institute Portal is Ready',
                'body' => '<div style="font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',sans-serif;max-width:600px;margin:20px auto;padding:40px;border:1px solid #eef2f6;border-radius:24px;color:#2d3436;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.02);">
                    <div style="text-align:center;margin-bottom:32px;">
                        <h2 style="color:#009e7e;font-size:32px;font-weight:800;margin:0;letter-spacing:-1px;">Welcome to Isoftro ERP</h2>
                        <p style="color:#636e72;margin-top:8px;font-size:16px;">Your digital institute management platform is live!</p>
                    </div>
                    
                    <p style="font-size:16px;margin-bottom:24px;line-height:1.6;">Hello <strong style="color:#111;">{{admin_name}}</strong>,</p>
                    
                    <p style="font-size:15px;margin-bottom:28px;line-height:1.6;color:#636e72;">Congratulations! <strong>{{institute_name}}</strong> is now registered on Isoftro ERP. Your dedicated portal has been successfully created and is ready for use.</p>
                    
                    <div style="background:#f8fafc;padding:24px;border-radius:20px;margin-bottom:32px;border:1px solid #f1f5f9;box-shadow:inset 0 2px 4px rgba(0,0,0,0.02);">
                        <h3 style="font-size:12px;text-transform:uppercase;letter-spacing:1.5px;color:#94a3b8;margin-top:0;margin-bottom:20px;font-weight:700;">Admin Access Credentials</h3>
                        <div style="margin-bottom:16px;">
                            <p style="margin:0 0 4px;font-size:13px;color:#94a3b8;">Portal URL</p>
                            <a href="{{login_url}}" style="color:#009e7e;text-decoration:none;font-weight:700;font-size:15px;">{{login_url}}</a>
                        </div>
                        <div style="margin-bottom:16px;">
                            <p style="margin:0 0 4px;font-size:13px;color:#94a3b8;">Admin Email</p>
                            <p style="margin:0;font-size:15px;color:#2d3436;font-weight:600;">{{admin_email}}</p>
                        </div>
                        <div>
                            <p style="margin:0 0 4px;font-size:13px;color:#94a3b8;">Temporary Password</p>
                            <p style="margin:0;font-size:16px;color:#009e7e;font-weight:800;letter-spacing:1px;font-family:\'Courier New\', Courier, monospace;">{{admin_pass}}</p>
                        </div>
                    </div>

                    <div style="background:linear-gradient(135deg, #009e7e 0%, #007d63 100%);padding:28px;border-radius:20px;margin-bottom:32px;color:#ffffff;box-shadow:0 12px 24px rgba(0,158,126,0.2);">
                        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;margin-top:0;margin-bottom:16px;opacity:0.9;font-weight:700;">Next Steps to Success</h3>
                        <div style="font-size:14px;line-height:1.6;">
                            <div style="margin-bottom:12px;display:flex;align-items:center;">
                                <span style="background:rgba(255,255,255,0.2);width:24px;height:24px;border-radius:50%;display:inline-block;text-align:center;line-height:24px;margin-right:12px;font-weight:bold;">1</span>
                                <span>Log in to your Dashboard</span>
                            </div>
                            <div style="margin-bottom:12px;display:flex;align-items:center;">
                                <span style="background:rgba(255,255,255,0.2);width:24px;height:24px;border-radius:50%;display:inline-block;text-align:center;line-height:24px;margin-right:12px;font-weight:bold;">2</span>
                                <span>Configure Institute Settings</span>
                            </div>
                            <div style="margin-bottom:12px;display:flex;align-items:center;">
                                <span style="background:rgba(255,255,255,0.2);width:24px;height:24px;border-radius:50%;display:inline-block;text-align:center;line-height:24px;margin-right:12px;font-weight:bold;">3</span>
                                <span>Add your first Batch and Students</span>
                            </div>
                        </div>
                    </div>

                    <div style="text-align:center;margin-bottom:32px;">
                        <a href="{{login_url}}" style="display:inline-block;padding:18px 44px;background:#009e7e;color:#ffffff;text-decoration:none;border-radius:14px;font-weight:800;font-size:16px;box-shadow:0 8px 20px rgba(0,158,126,0.3);transition:all 0.3s ease;">Launch Your Portal →</a>
                    </div>

                    <p style="font-size:13px;color:#94a3b8;line-height:1.6;text-align:center;margin-bottom:40px;">Need any assistance? Our world-class support team is just a reply away. We\'re here to help you scale your institute.</p>

                    <div style="border-top:1px solid #f1f5f9;padding-top:32px;text-align:center;">
                        <p style="margin:0;font-size:14px;color:#94a3b8;">Powered by</p>
                        <p style="margin:4px 0 0;font-size:20px;font-weight:900;color:#009e7e;letter-spacing:-0.5px;">Isoftro<span style="color:#2d3436;">ERP</span></p>
                    </div>
                </div>'
            ]
        ];
