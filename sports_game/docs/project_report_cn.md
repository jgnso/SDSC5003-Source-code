# 第15届全运会信息系统 —— 中文项目报告

> 更新日期：2025-12-07

## 1. 项目定位与技术栈
- **用途：** 数据库原理课程的综合实践，集公众查询与管理员后台于一体。
- **技术：** 纯 PHP（无框架）+ SQLite (`data.db`) + 原生 HTML/CSS。
- **角色入口：** `index.php` 将用户分流至公众查询页 `public.php` 和后台入口（`admin_login.php`）。所有界面新增中英切换按钮，语言偏好写入 session + cookie 持久化，初次打开默认展示英文界面（EN）。

## 2. 顶层页面与导航
| 文件 | 作用 | 说明 |
| --- | --- | --- |
| `index.php` | 登陆页 | 两张角色卡片 + 语言切换器（默认 EN），并扩展为占据 ~96vw 的响应式卡片布局。 |
| `public.php` | 公众查询面板 | 卡片式导航，指向 5 个查询页面，并提供返回首页的按钮，自动继承语言偏好。 |
| `admin_dashboard.php` | 管理控制台 | 登录后展示实时统计卡 + 3×2 功能卡片栅格，均可继承语言偏好。 |
| `admin.html` | 旧版控制台 | 仍然存在但已立刻跳转到登录页，避免绕过认证。 |

## 3. 公众查询端（`*_list.php`）
所有查询页遵循统一流程：连接 SQLite → 读取 GET 参数 → 构建 SQL（多数采用预处理）→ 渲染表格并显示记录数。

| 文件 | 主要字段/功能 | 特点 |
| --- | --- | --- |
| `athlete_list.php` | `Athlete`（ID/姓名/年龄/性别/代表团） | 支持按编号或姓名模糊查询，统计记录数并提示未命中。 |
| `delegation_list.php` | `Delegation`（ID/地区/地址） | 可按 ID、地区、地址模糊搜索。 |
| `category_list.php` | `Category`（ID/名称/负责人） | 同样支持多字段模糊搜索。 |
| `event_list.php` | `Event` + `Category` | 通过 LEFT JOIN 显示赛事所属大项，可按多字段筛选。 |
| `participation_list.php` | `Participation` + `Athlete` + `Event` | 同时提供关键词搜索与奖牌筛选，并对奖牌进行颜色标记；附带 `debug` 参数输出 SQL。 |

## 4. 管理后台模块（`admin_*.php`）
### 共性
- 均直接使用 `data.db`，包含添加/删除（部分含更新）逻辑，操作完成后重定向回列表。
- 编辑入口由 `admin_*_edit.php` 负责，读取单条记录并通过 POST 回主管理页。

### 各模块概览
| 实体 | 主页面 | 编辑页面 | 细节 |
| --- | --- | --- | --- |
| 代表团 | `admin_delegation.php` | `admin_delegation_edit.php` | 支持新增、修改、删除，字段 `Delegation_id/Region/Address`。 |
| 分类 | `admin_category.php` | `admin_category_edit.php` | 同步维护 `Category_id/Category_name/Manager`。 |
| 赛事 | `admin_event.php` | `admin_event_edit.php` | 添加赛事时拉取 `Category` 下拉框；列表展示所属分类。 |
| 运动员 | `admin_athlete.php` | `admin_athlete_edit.php` | 已补齐更新逻辑，并统一使用语言切换 + toast。 |
| 参赛记录 | `admin_participation.php` | `admin_participation_edit.php` | 已切换到统一 UI，增删改全程使用预处理 + 事务；成绩/用时必填、奖牌标准化（Gold/Silver/Bronze/None）并配合唯一索引避免重复，同时提供 toast 提示与操作确认。 |

### 安全与认证支撑文件
| 文件 | 功能 |
| --- | --- |
| `admin_login.php` | 负责登录表单、密码哈希校验、默认管理员初始化与跳转。 |
| `admin_dashboard.php` | 登录后的新版控制台（取代旧 `admin.html`），顶端展示实时数据卡，主体卡片固定为 3×2 栅格，并提供带确认的注销入口。 |
| `auth_guard.php` | 被每个 `admin_*.php` 引入，用于校验会话并在未登录时安全跳转登录页。 |
| `admin_security.php` | 封装 `AdminUsers` 表的建表/种子逻辑以及时间戳更新。 |
| `admin_users.php` | 提供管理员账号的创建、密码重置与删除界面（受登录保护）。 |
| `logout.php` | 退出并清理会话。 |
| `admin.html` | 旧页面已改为自动跳转到登录页，避免用户访问旧地址迷路。 |

## 5. 数据结构（基于 SQL 推断）
- `Delegation(Delegation_id, Region, Address)`
- `Athlete(Athlete_id, Name, Age, Gender, DelegationID)`
- `Category(Category_id, Category_name, Manager)`
- `Event(EventID, CategoryID, EventName, Level)`
- `Participation(AthleteID, EventID, Time, Medal)`
> Participation 表已通过 `db_schema.php` 将空 Medal 统一写成 `None`，并创建 `(AthleteID, EventID, Medal)` 唯一索引，避免重复记录。

## 6. 现存不足与改进提示
1. **操作日志：** Toast 已解决即时反馈，但仍缺乏持久化的操作审计（数据库/文件日志）。
2. **数据字典：** 尚未在 `docs/` 中输出正式的 ER 图与字段说明，答辩材料有待补充。
3. **批量工具：** 缺少一键化的初始化/备份脚本（批量 `php -l`、SQLite dump 等）。
4. **统计展现：** 公众端暂无奖牌统计/可视化，可作为下一阶段的功能加分点。
5. **自动化测试：** 目前仅通过手工测试和 `php -l`，可补充 smoke script 或简单的回归用例。

## 7. 下一步（与本次任务相关）
- 在 `docs/task.md` 里维护数据库与安全改进计划。
- 实现管理员登录、凭证存储、会话保护及退出机制，并为后续 SQL 硬化奠定基础。
- 在保障安全的前提下继续完善 CRUD 功能和 UI。

如需查看英文版详细说明，请参考 `docs/project_overview.md`。