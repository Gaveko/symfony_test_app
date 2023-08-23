# Todo App API

## Usage

Before you can use API you must to register your User.
You can use "http://domain/api/registration" endpoint.
After registration for token update you can use 
"http://domain/api/login" endpoint.

All main endpoinst accessible from /api/todos endpoint.
------------------- -------- -------- ------ -------------------------- 
Name                Method   Scheme   Host   Path                      
------------------- -------- -------- ------ -------------------------- 
all_todos           GET      ANY      ANY    /api/todos                
create_todos        POST     ANY      ANY    /api/todos                
app_todo_update     PUT      ANY      ANY    /api/todos/{id}           
app_todo_delete     DELETE   ANY      ANY    /api/todos/{id}           
app_todo_search     GET      ANY      ANY    /api/todos/search         
app_todo_complete   ANY      ANY      ANY    /api/todos/complete/{id}

## Search endpoint

For search endpoint you must pass "q" query param with a searching value.

## Filter and sorting

For filter by criteria "isDone" you must pass 0 or 1 value.

For sorting by growth you can use "entityField" query param and "-entityField" for decline.

