# Realtime Kanban Board

Laravel 12 기반 실시간 칸반 보드 애플리케이션. Laravel Reverb를 통한 WebSocket 실시간 동기화, 역할 기반 접근 제어, 카드 검색/필터링, 다크 모드를 지원합니다.

## 주요 기능

- **실시간 동기화**: Laravel Reverb WebSocket을 통한 카드/컬럼 변경 즉시 반영
- **역할 기반 접근 제어**: Owner / Editor / Viewer 3단계 권한
- **카드 관리**: 드래그 앤 드롭 이동, 우선순위, 담당자 배정, 마감일
- **검색 & 필터링**: Laravel Scout 기반 전체 검색, 우선순위/담당자/마감일 필터
- **댓글**: 카드별 실시간 댓글
- **알림**: 카드 배정, 마감일 임박, 댓글 알림
- **다크 모드**: Tailwind CSS dark mode 토글
- **성능 최적화**: Redis 캐싱, Eager Loading, 데이터베이스 인덱싱

## 기술 스택

- **Backend**: PHP 8.4, Laravel 12
- **Frontend**: Blade, Tailwind CSS, Alpine.js
- **WebSocket**: Laravel Reverb
- **Database**: SQLite
- **Cache & Queue**: Redis
- **Search**: Laravel Scout (Database Driver)
- **Build**: Node.js, Vite

## 요구 사항

- PHP 8.4+
- Composer 2.x
- Node.js 18+ (Vite 빌드용)
- Redis 7+
- SQLite 3

## 로컬 설치

```bash
# 1. 저장소 클론
git clone <repository-url>
cd realtime-kanban-board

# 2. 의존성 설치 및 초기 설정
composer setup

# 3. 환경 설정 (.env 수정)
# DB, Redis, Reverb 설정 확인

# 4. 시드 데이터 (선택)
php artisan db:seed

# 5. 개발 서버 실행
composer dev
```

`composer dev` 실행 시 다음 서비스가 동시에 시작됩니다:
- Laravel 개발 서버 (http://localhost:8000)
- Queue Worker
- Laravel Pail (로그 모니터링)
- Vite 개발 서버

### 수동 실행 (실시간 동기화 포함)

`composer dev`를 사용하지 않는 경우, 아래 프로세스를 **각각 별도 터미널**에서 실행하세요:

```bash
# 터미널 1: 웹 서버
php artisan serve

# 터미널 2: Reverb WebSocket 서버 (실시간 동기화 필수)
php artisan reverb:start

# 터미널 3: Vite 개발 서버 (CSS/JS 핫 리로드)
npm run dev
```

> **참고**: 실시간 동기화(다른 브라우저에서 변경 사항 즉시 반영)를 사용하려면 **Reverb 서버가 반드시 실행 중**이어야 합니다.
> WebSocket은 기본적으로 `ws://localhost:8080`으로 연결됩니다.

## 사용 방법

### 1. 회원가입 / 로그인

브라우저에서 `http://localhost:8000`에 접속하여 회원가입 또는 로그인합니다.

시드 데이터 사용 시 아래 계정으로 바로 로그인할 수 있습니다:

| 이메일 | 비밀번호 |
|---|---|
| admin@example.com | password |

### 2. 보드 생성 및 관리

1. **보드 생성**: "내 보드" 페이지에서 `+ 새 보드` 버튼 클릭
2. **컬럼 추가**: 보드 진입 후 `+ 첫 컬럼 추가` 또는 `+ 컬럼 추가` 클릭 (예: To Do, In Progress, Done)
3. **카드 추가**: 각 컬럼 하단의 `+ 카드 추가` 클릭 → 제목, 설명, 우선순위, 마감일, 담당자 설정

### 3. 드래그 앤 드롭

- **카드 이동**: 카드를 드래그하여 같은 컬럼 내 순서 변경 또는 다른 컬럼으로 이동
- **컬럼 이동**: 컬럼 헤더의 드래그 핸들(⠿)로 컬럼 순서 변경
- 모바일에서도 터치 제스처로 드래그 앤 드롭 가능

### 4. 실시간 협업

1. 보드에 멤버를 추가합니다 (사이드바 → 멤버 탭 → 이메일/이름 검색)
2. 멤버에게 역할을 부여합니다:
   - **Owner**: 보드 수정/삭제, 멤버 관리, 모든 CRUD
   - **Editor**: 컬럼/카드/댓글 CRUD
   - **Viewer**: 읽기 전용
3. 다른 브라우저에서 동일 보드 접속 시, 한쪽의 변경 사항이 **즉시** 다른 쪽에 반영됩니다

### 5. 검색 및 필터링

- **검색**: 상단 검색창에서 카드 제목/설명 검색
- **필터**: 우선순위, 담당자, 마감일 드롭다운으로 카드 필터링

### 6. 내보내기

보드 헤더의 내보내기(📄) 버튼으로 JSON 또는 Markdown 형식으로 보드를 내보낼 수 있습니다.

### 7. 설정

프로필 메뉴 → 설정에서:
- 프로필 정보 수정
- 테마 변경 (라이트/다크 모드)
- 알림 설정 (카드 배정, 댓글, 마감일)

## Docker로 실행

```bash
# 빌드 및 실행
docker compose up -d

# 마이그레이션 실행
docker compose exec app php artisan migrate --force

# 시드 데이터 (선택)
docker compose exec app php artisan db:seed

# 접속
# App: http://localhost:8000
# WebSocket: ws://localhost:8080
```

### Docker 구성

- **app**: PHP-FPM + Nginx + Queue Worker + Reverb + Scheduler
- **mysql**: MySQL 8.0
- **redis**: Redis 7

## 테스트

```bash
# 전체 테스트 실행
php artisan test

# 특정 테스트 스위트
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# 코드 스타일 검사
vendor/bin/pint --test

# 코드 스타일 자동 수정
vendor/bin/pint
```

### 테스트 커버리지

- **Feature Tests**: BoardController, CardController, Authorization, Realtime (41 tests)
- **Unit Tests**: ActivityService, CardMovement (14 tests)

## API 엔드포인트

모든 API는 세션 인증 필요 (`web` + `auth` 미들웨어). Rate Limiting 적용 (60 req/min).

### 보드 (Web Routes)

| Method | URI | 설명 |
|--------|-----|------|
| GET | `/boards` | 보드 목록 |
| POST | `/boards` | 보드 생성 |
| GET | `/boards/{id}` | 보드 상세 |
| PUT | `/boards/{id}` | 보드 수정 |
| DELETE | `/boards/{id}` | 보드 삭제 (Owner만) |

### 컬럼

| Method | URI | 설명 |
|--------|-----|------|
| POST | `/api/boards/{board}/columns` | 컬럼 생성 |
| PUT | `/api/boards/{board}/columns/{column}` | 컬럼 수정 |
| DELETE | `/api/boards/{board}/columns/{column}` | 컬럼 삭제 |
| POST | `/api/boards/{board}/columns/{column}/reorder` | 컬럼 순서 변경 |

### 카드

| Method | URI | 설명 |
|--------|-----|------|
| POST | `/api/boards/{board}/columns/{column}/cards` | 카드 생성 |
| PUT | `/api/boards/{board}/cards/{card}` | 카드 수정 |
| DELETE | `/api/boards/{board}/cards/{card}` | 카드 삭제 |
| POST | `/api/boards/{board}/cards/{card}/move` | 카드 이동 |

### 댓글

| Method | URI | 설명 |
|--------|-----|------|
| GET | `/api/boards/{board}/cards/{card}/comments` | 댓글 목록 |
| POST | `/api/boards/{board}/cards/{card}/comments` | 댓글 작성 |
| DELETE | `/api/boards/{board}/cards/{card}/comments/{comment}` | 댓글 삭제 |

### 멤버

| Method | URI | 설명 |
|--------|-----|------|
| GET | `/api/boards/{board}/members` | 멤버 목록 |
| GET | `/api/boards/{board}/members/search-users` | 사용자 검색 |
| POST | `/api/boards/{board}/members` | 멤버 추가 (Owner만) |
| PUT | `/api/boards/{board}/members/{member}` | 역할 변경 |
| DELETE | `/api/boards/{board}/members/{member}` | 멤버 제거 |

### 검색 & 필터

| Method | URI | 설명 |
|--------|-----|------|
| GET | `/api/boards/{board}/search?q={query}` | 카드 검색 |
| GET | `/api/boards/{board}/filter` | 카드 필터링 |

**필터 파라미터**: `assigned_user_id`, `priority` (low/medium/high/urgent), `due_filter` (today/this_week/overdue)

### 알림

| Method | URI | 설명 |
|--------|-----|------|
| GET | `/api/notifications` | 알림 목록 |
| POST | `/api/notifications/{id}/read` | 읽음 처리 |
| POST | `/api/notifications/read-all` | 전체 읽음 |

### 활동 로그

| Method | URI | 설명 |
|--------|-----|------|
| GET | `/api/boards/{board}/activities` | 활동 로그 |

## WebSocket 이벤트

채널: `private-board.{boardId}`

| 이벤트 | 설명 |
|--------|------|
| `CardCreated` | 카드 생성 |
| `CardUpdated` | 카드 수정 |
| `CardDeleted` | 카드 삭제 |
| `CardMoved` | 카드 이동 |
| `ColumnCreated` | 컬럼 생성 |
| `ColumnUpdated` | 컬럼 수정 |
| `ColumnDeleted` | 컬럼 삭제 |
| `CommentCreated` | 댓글 작성 |
| `ActivityLogged` | 활동 로그 |

## 역할 권한

| 기능 | Owner | Editor | Viewer |
|------|-------|--------|--------|
| 보드 조회 | O | O | O |
| 보드 수정/삭제 | O | X | X |
| 컬럼/카드 CRUD | O | O | X |
| 댓글 작성 | O | O | X |
| 멤버 관리 | O | X | X |
| 활동 로그 조회 | O | O | O |

## 모델 관계

```
User
├── hasMany -> Board (소유한 보드)
├── belongsToMany -> Board (멤버 보드, board_members)
├── hasMany -> Card (assigned_user_id)
└── hasMany -> Comment

Board
├── belongsTo -> User (소유자)
├── hasMany -> Column
├── hasMany -> BoardMember
├── hasMany -> Activity
└── hasManyThrough -> Card (Column 경유)

Column
├── belongsTo -> Board
└── hasMany -> Card

Card
├── belongsTo -> Column
├── belongsTo -> User (assignedUser)
└── hasMany -> Comment

Comment
├── belongsTo -> Card
└── belongsTo -> User

BoardMember
├── belongsTo -> Board
└── belongsTo -> User
```

## 보안

- CSRF 보호 (세션 기반 인증)
- XSS 방지 (Blade 이스케이핑)
- SQL Injection 방지 (Eloquent ORM)
- Rate Limiting (API: 60 req/min, Search: 30 req/min)
- 역할 기반 접근 제어 (Policy)

## 프로덕션 배포 체크리스트

1. `.env` 설정: `APP_ENV=production`, `APP_DEBUG=false`
2. `APP_KEY` 생성: `php artisan key:generate`
3. 데이터베이스 설정 (SQLite 또는 MySQL)
4. Redis 서버 설정
5. `composer install --no-dev --optimize-autoloader`
6. `npm ci && npm run build`
7. `php artisan migrate --force`
8. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
9. Queue Worker 데몬 설정
10. Reverb WebSocket 서버 시작
11. Scheduler (cron): `* * * * * php artisan schedule:run`

## 라이선스

MIT License
