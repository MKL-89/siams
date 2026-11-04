# SIAMS test accounts (development only)

These accounts are created by `database/seed.php`. All names, emails and
organizations are fictitious. Do not use them on a live server.

Password for every account: `Siams@2026`

| Role | Email |
|---|---|
| Administrator | admin@siams.test |
| Attachment Coordinator | coordinator@siams.test |
| University Supervisor | lecturer@siams.test |
| Industrial Supervisor | industrial@siams.test |
| Student | student@siams.test |
| Student (deactivated, cannot log in) | inactive@siams.test |

## Sample host organizations

`database/sample_opportunities.php` (also run by `seed.php`) adds 14 fictitious
organizations with 32 opportunities. Each has an industrial supervisor account
with the same password:

| Organization | Supervisor login |
|---|---|
| Demo Rift Valley Bank – ICT Department | rvbank@siams.test |
| Sample Teaching and Referral Hospital | tthospital@siams.test |
| Demo Mobile Networks Ltd | mobilenet@siams.test |
| Sample Agro Processors Ltd | agroproc@siams.test |
| Demo Software Studio | swstudio@siams.test |
| Sample Lakeside Water and Sanitation Company | lakewater@siams.test |
| Demo Coast Logistics Ltd | coastlog@siams.test |
| Sample Girls High School | girlshigh@siams.test |
| Demo Community Health Initiative | commhealth@siams.test |
| Sample Highland Dairy Manufacturers Ltd | dairy@siams.test |
| Demo Savings and Credit Co-operative | sacco@siams.test |
| Sample County Revenue Office | revenue@siams.test |
| Demo Lakeview Hotel and Conference Centre | hotel@siams.test |
| Sample Lake Basin Internet Services | isp@siams.test |

New student accounts created through **Create a student account** on the login
page stay pending until the coordinator approves them under
**Dashboard → Manage student accounts**.
