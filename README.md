# Very simple api

## Details

* symfony 7
* php 8.2
* mysql 8

Make migration  before start.

Example for .env: ./.env.example



## Routes
 
### List read resume
`GET /candidate/read`

Available query params:
* `sort` - `first_name`|`last_name`|`salary`|`position`|`created_at`|`updated_at`
* `sortDirection`: `ASC`|`DESC`

### List unread resume
`GET /candidate/unread`

Available query params:
* `sort` - `first_name`|`last_name`|`salary`|`position`|`created_at`|`updated_at`
* `sortDirection`: `ASC`|`DESC`

### Get single resume
`GET /candidate/{candidateId}`

This endpoint also will set resume as read.

Unfortunately, this goes against the CQRS principle.

### Add new resume
`POST /candidate`
content/type: application/json

request body:
```
{
"first_name" : sting,
"last_name": string,
"email": email,
"salary": integer,
"position": string,
"phone": string
}
```