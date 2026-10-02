#!/usr/bin/env bash
set -euo pipefail

BASE_URL=${E2E_BASE_URL:-http://localhost:8090}
RUN_ID=$(date +%s%N)
TMP=$(mktemp)
trap 'rm -f "$TMP"' EXIT

fail() { printf 'FAIL: %s\n' "$1" >&2; exit 1; }
api() {
  local method=$1 path=$2 token=${3:-} body='' expected status
  if [[ $# == 4 ]]; then expected=$4; else body=$4; expected=$5; fi
  local args=(-sS -o "$TMP" -w '%{http_code}' -X "$method" "$BASE_URL$path" -H 'Content-Type: application/json')
  [[ -z "$token" ]] || args+=(-H "Authorization: Bearer $token")
  [[ -z "$body" ]] || args+=(-d "$body")
  status=$(curl "${args[@]}") || fail "curl $method $path"
  [[ "$status" == "$expected" ]] || fail "$method $path returned HTTP $status (expected $expected)"
}
value() { jq -er "$1" "$TMP" 2>/dev/null || fail "missing response field: $1"; }
has_value() {
  local description=$1
  shift
  jq -e "$@" "$TMP" >/dev/null 2>&1 || fail "response assertion failed: $description"
}
login() {
  api POST /auth/login '' "$(jq -nc --arg email "$1" --arg password "$2" '{email:$email,password:$password}')" 200
  value '.token'
}

ADMIN_EMAIL="admin-${RUN_ID}@example.test"
TEACHER_EMAIL="teacher-${RUN_ID}@example.test"
OTHER_TEACHER_EMAIL="teacher2-${RUN_ID}@example.test"
STUDENT_EMAIL="student-${RUN_ID}@example.test"
OTHER_STUDENT_EMAIL="student2-${RUN_ID}@example.test"
INITIAL_PASSWORD='InitialPass123!'
RESET_PASSWORD='ResetPass123!'

api POST /auth/register-school '' "$(jq -nc --arg email "$ADMIN_EMAIL" --arg password "$INITIAL_PASSWORD" --arg name "E2E School $RUN_ID" '{school_name:$name,first_name:"E2E",last_name:"Admin",email:$email,password:$password}')" 201
ADMIN_TOKEN=$(login "$ADMIN_EMAIL" "$INITIAL_PASSWORD")
api POST /cycles "$ADMIN_TOKEN" '{"name":"E2E cycle","starts_on":"2026-01-01","ends_on":"2027-12-31"}' 201
CYCLE_ID=$(value '.data.id')

api POST /users/teachers "$ADMIN_TOKEN" "$(jq -nc --arg email "$TEACHER_EMAIL" --arg password "$INITIAL_PASSWORD" '{first_name:"E2E",last_name:"Teacher",email:$email,password:$password}')" 201
TEACHER_ID=$(value '.data.id')
api POST /users/teachers "$ADMIN_TOKEN" "$(jq -nc --arg email "$OTHER_TEACHER_EMAIL" --arg password "$INITIAL_PASSWORD" '{first_name:"Other",last_name:"Teacher",email:$email,password:$password}')" 201
OTHER_TEACHER_ID=$(value '.data.id')
api POST /subjects "$ADMIN_TOKEN" "$(jq -nc --argjson cycle "$CYCLE_ID" --argjson teacher "$TEACHER_ID" '{cycle_id:$cycle,teacher_id:$teacher,name:"E2E subject"}')" 201
SUBJECT_ID=$(value '.data.id')
api POST /subjects "$ADMIN_TOKEN" "$(jq -nc --argjson cycle "$CYCLE_ID" --argjson teacher "$OTHER_TEACHER_ID" '{cycle_id:$cycle,teacher_id:$teacher,name:"Other subject"}')" 201
OTHER_SUBJECT_ID=$(value '.data.id')

api POST /users/students "$ADMIN_TOKEN" "$(jq -nc --arg email "$STUDENT_EMAIL" --arg password "$INITIAL_PASSWORD" --arg enrollment "E2E-$RUN_ID" '{first_name:"E2E",last_name:"Student",email:$email,password:$password,enrollment_number:$enrollment,birth_date:"2010-01-01"}')" 201
STUDENT_ID=$(value '.data.id')
api POST /users/students "$ADMIN_TOKEN" "$(jq -nc --arg email "$OTHER_STUDENT_EMAIL" --arg password "$INITIAL_PASSWORD" --arg enrollment "E2E2-$RUN_ID" '{first_name:"Other",last_name:"Student",email:$email,password:$password,enrollment_number:$enrollment,birth_date:"2010-02-02"}')" 201
OTHER_STUDENT_ID=$(value '.data.id')

api POST /groups "$ADMIN_TOKEN" "$(jq -nc --argjson subject "$SUBJECT_ID" '{name:"E2E group",subject_ids:[$subject]}')" 201
GROUP_ID=$(value '.data.id')
api POST "/groups/$GROUP_ID/students" "$ADMIN_TOKEN" "$(jq -nc --argjson student "$STUDENT_ID" '{student_id:$student}')" 201
api POST "/groups/$GROUP_ID/students" "$ADMIN_TOKEN" "$(jq -nc --argjson student "$OTHER_STUDENT_ID" '{student_id:$student}')" 201

TEACHER_TOKEN=$(login "$TEACHER_EMAIL" "$INITIAL_PASSWORD")
api POST "/subjects/$SUBJECT_ID/tasks" "$TEACHER_TOKEN" '{"name":"E2E task","description":"Flow check","due_at":"2027-01-01T00:00:00Z"}' 201
TASK_ID=$(value '.data.id')
STUDENT_TOKEN=$(login "$STUDENT_EMAIL" "$INITIAL_PASSWORD")
api GET /me/tasks "$STUDENT_TOKEN" 200
has_value 'task appears pending by default' ".data | length == 1 and .[0].task.id == $TASK_ID and .[0].delivery.status == \"pending\""
DELIVERY_ID=$(value '.data[0].delivery.id')
api POST "/deliveries/$DELIVERY_ID/comments" "$STUDENT_TOKEN" '{"body":"Student question"}' 201
api POST "/deliveries/$DELIVERY_ID/comments" "$TEACHER_TOKEN" '{"body":"Teacher reply"}' 201
api GET "/deliveries/$DELIVERY_ID/comments" "$TEACHER_TOKEN" 200
has_value 'teacher can read the two-message thread' '.data | length == 2'
OTHER_STUDENT_TOKEN=$(login "$OTHER_STUDENT_EMAIL" "$INITIAL_PASSWORD")
api GET "/deliveries/$DELIVERY_ID/comments" "$OTHER_STUDENT_TOKEN" 404

api PUT "/deliveries/$DELIVERY_ID/delivered" "$TEACHER_TOKEN" '{}' 200
api PUT "/deliveries/$DELIVERY_ID/grade" "$TEACHER_TOKEN" '{"grade":90}' 200
api GET /me/tasks?status=graded "$STUDENT_TOKEN" 200
has_value 'student sees graded status and grade 90' ".data | length == 1 and .[0].delivery.status == \"graded\" and .[0].delivery.grade == 90"

api POST /calendar/events "$ADMIN_TOKEN" '{"title":"E2E event","description":"Calendar flow","starts_at":"2026-10-02T10:00:00Z","ends_at":"2026-10-02T11:00:00Z"}' 201
EVENT_TITLE=$(value '.data.title')
TODAY=$(date +%F)
api POST /announcements "$ADMIN_TOKEN" "$(jq -nc --arg today "$TODAY" '{title:"E2E announcement",body:"Announcement flow",starts_on:$today,ends_on:$today}')" 201
ANNOUNCEMENT_TITLE=$(value '.data.title')
api GET '/calendar?from=2026-01-01T00:00:00Z&to=2027-12-31T23:59:59Z' "$STUDENT_TOKEN" 200
has_value 'student sees school calendar event' --arg title "$EVENT_TITLE" '.data | any(.title == $title)'
api GET /announcements/active "$STUDENT_TOKEN" 200
has_value 'student sees active announcement' --arg title "$ANNOUNCEMENT_TITLE" '.data | any(.title == $title)'

api POST "/cycles/$CYCLE_ID/finish" "$ADMIN_TOKEN" '{}' 200
api GET "/subjects/$SUBJECT_ID" "$ADMIN_TOKEN" 200
has_value 'finishing the cycle finishes its subject' '.data.status == "finished"'
api PUT "/users/$STUDENT_ID/password" "$ADMIN_TOKEN" "$(jq -nc --arg password "$RESET_PASSWORD" '{new_password:$password}')" 200
STUDENT_TOKEN=$(login "$STUDENT_EMAIL" "$RESET_PASSWORD")
api PATCH "/users/students/$OTHER_STUDENT_ID/status" "$ADMIN_TOKEN" '{"status":"inactive"}' 200
api POST /auth/login '' "$(jq -nc --arg email "$OTHER_STUDENT_EMAIL" --arg password "$INITIAL_PASSWORD" '{email:$email,password:$password}')" 401
api GET "/subjects/$OTHER_SUBJECT_ID" "$TEACHER_TOKEN" 404
api POST "/subjects/$SUBJECT_ID/tasks" "$STUDENT_TOKEN" '{"name":"Forbidden task","description":"","due_at":"2027-01-01T00:00:00Z"}' 403

printf 'PASS: full E2E flow (school %s, cycle %s)\n' "$RUN_ID" "$CYCLE_ID"
