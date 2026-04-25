CaneZone Assessment Platform - ระบบประเมินศักยภาพแปลงอ้อย

Project folder name:
canezone_assessment_platform

Server-ready files:
- index.html       : หน้า Login
- dashboard.html   : หน้า Dashboard ภาพรวม
- plots.html       : หน้าเลือกแปลงจากตารางและ Maps
- assessment.html  : หน้าบันทึกข้อมูลประเมินแปลงอ้อย
- README.txt       : คำอธิบายการใช้งาน

Navigation flow:
1. index.html
   -> กดเข้าสู่ระบบ
2. dashboard.html
   -> กดเมนู เลือกแปลง หรือรายการที่ต้องติดตาม
3. plots.html
   -> กดเลือกแปลงนี้ หรือ ไปหน้าบันทึกประเมิน
4. assessment.html
   -> กดบันทึก กลับไป dashboard.html

Project naming:
ชื่อ Project แนะนำ: CaneZone Assessment Platform
เหตุผล:
- ไม่ผูกกับเขต 7 เท่านั้น
- รองรับการขยายไปเขตอื่นได้ในอนาคต
- ใช้ได้ทั้ง Dashboard, Maps, Field Assessment และ Report
- สามารถเพิ่มตัวกรอง Area/Region/Zone ได้ภายหลัง

Theme:
- รองรับโหมดมืด/สว่าง
- ปุ่มอยู่บนแถบเมนูด้านบน หรือหน้า Login
- จดจำค่าด้วย localStorage key: canezone-theme

Deployment:
- อัปโหลดไฟล์ทั้งหมดในโฟลเดอร์นี้ขึ้น server เดียวกัน
- ตั้งค่า web server ให้เปิด index.html เป็นหน้าแรก
- สำหรับระบบจริงควรแยก CSS/JS เป็น assets ภายหลัง และเชื่อมต่อ Backend/Database/Auth จริง
