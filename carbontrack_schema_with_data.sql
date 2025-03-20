
-- 创建用户表
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    carbon_credits INT DEFAULT 0
);

-- 创建碳减排行为表
CREATE TABLE carbon_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity VARCHAR(255) NOT NULL,
    quantity FLOAT NOT NULL,
    carbon_reduction FLOAT NOT NULL,
    points INT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 创建碳减排因子表
CREATE TABLE carbon_factors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity VARCHAR(255) UNIQUE NOT NULL,
    unit VARCHAR(50) NOT NULL,
    reduction_factor FLOAT NOT NULL,
    bonus_points INT DEFAULT 0
);

INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES ('旧衣回收1kg / Recycle 1kg old clothes', 'kg', 3.6, 0);
INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES ('二手交易1次 / Second-hand transaction 1 time', 'h', 10, 4);
INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES ('衣物租赁1次 / Clothing rental service 1 time', 'h', 10, 4);
INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES ('减少肉类消费1kg / Reduce meat consumption 1kg', 'kg', 15.54, 0);
INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES ('光盘行动1次 / Finish everything on your plate 1 time', 'h', 0.0329041095890411, 0);
INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES ('居家回收利用', '次/times', 10, 4);
INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES ('公交出行1km / Bus transport 1km', 'km', 0.094, 0);
INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES ('地铁出行1km / Subway travel 1km', 'km', 0.089, 0);
INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES ('步行1km / Walk 1km', 'km', 0.135, 0);
INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES ('骑行1km / Cycle 1km', 'km', 0.05, 0);
INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES ('拼车1km / Carpool 1km', 'km', 0.0675, 0);
INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES ('上网课1h / Online class 1h', 'h', 0.15, 0);
INSERT INTO carbon_factors (activity, unit, reduction_factor, bonus_points) VALUES ('提交电子作业1次 / Write assignment electronically 1 time', '次/times', 0.05, 0);