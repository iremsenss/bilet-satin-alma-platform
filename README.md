# Bilet Satın Alma Platformu

Dinamik, veritabanı destekli ve çok kullanıcı rolleri olan bir otobüs bilet satın alma platformu.

## Özellikler

- Kullanıcı Rolleri: Ziyaretçi, Yolcu, Firma Admin, Admin
- Sefer arama ve listeleme
- Bilet satın alma ve iptal
- PDF bilet üretimi
- Firma ve Admin paneli (CRUD işlemleri)
- Kupon yönetimi
- Docker ile paketlenmiş

## Teknolojiler

- PHP 8.2
- SQLite
- HTML, CSS, Bootstrap
- Docker & Docker Compose
- FPDF (PDF üretimi)

## Kurulum

1. Repo’yu klonlayın:

```bash
git clone https://github.com/iremsenss/bilet-satin-alma-platform.git
```

2. Proje dizinine gidin:

```bash
cd bilet-satin-alma-platform
```

3. Docker ile başlatın:

```bash
docker-compose up -d
```
4. Tarayıcıda açın:

```bash
http://localhost:8080
```

## Kullanıcılar 

Kullanıcı Rolü,E-posta Adresi,Şifre
Admin,admin@ticketbox.com,admin123
Firma Admin,aliosmanulusoy@ticketbox.com,123456
Firma Admin,metro@ticketbox.com,123456
Firma Admin,varan@ticketbox.com,123456
Firma Admin,pamukkale@ticketbox.com,123456
