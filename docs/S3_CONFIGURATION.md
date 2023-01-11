# Amazon S3 configuration
This project is currently using Amazon S3 as a backbone for filesystem. The filesystem is abstracted from the
actual implementation, though it might require changing some classes' implementations in order to switch from S3
to another implementation.

## Bucket settings
Make sure to un-tick the `Block all public access` (with all sub-settings) setting as the bucket will ignore the policy
that we attach to it to provide access to certain objects to the public.

## Bucket policy
For certain non-security-critical files to be accessible to the public it is necessary to attach the following bucket
policy to the S3 bucket that you have created:
```
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "Pub_ACC_To_Place_Photos",
            "Effect": "Allow",
            "Principal": "*",
            "Action": "s3:GetObject",
            "Resource": "arn:aws:s3:::test-sggw-meet-app-bucket/place_photo/*"
        },
        {
            "Sid": "Pub_ACC_To_Place_Menus",
            "Effect": "Allow",
            "Principal": "*",
            "Action": "s3:GetObject",
            "Resource": "arn:aws:s3:::test-sggw-meet-app-bucket/place_menu/*"
        }
    ]
}
```

## CORS settings
For the frontend/mobile app to be able to read the data stored in S3 you will need to attach the following CORS policy
to your S3 bucket:
```
[
    {
        "AllowedHeaders": [
            "*"
        ],
        "AllowedMethods": [
            "GET"
        ],
        "AllowedOrigins": [
            "*"
        ],
        "ExposeHeaders": []
    }
]
```