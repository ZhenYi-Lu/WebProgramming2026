<html>
<head>
    <title>數位馬戲團_入場申請表</title>
</head>
<body>
    <center>
            <h1>🎪數位馬戲團入場申請表</h1>
            歡迎，未來的表演者。<br>
            請填寫以下資料，讓我們為你安排最「適合」的角色。<br>
            <del>（別擔心，你沒有選擇權 🙂）</del><br>
    </center>
    <p><hr><p>
    <form action="ticket.php" method="">
        <h3>📌 基本資料</h3>  
        <ol>
            <li>姓名&nbsp&nbsp<input type="text" placeholder="your name" name="nName" value=""><br/>
            <li>性別&nbsp&nbsp<input type="radio" name="mGender" value="m">男 <input type="radio" name="mGender" value="f">女<br/>
            <li>出生日期&nbsp&nbsp<input type="date" name="nDate" value=""><br/>
            <li>聯絡電話&nbsp&nbsp<input type="text" placeholder="09XXXXXXXX" name="nNumber" value=""><br/>
            <li>Email&nbsp&nbsp<input type="text" name="nEmail" value=""><br/>
            <li>學校&nbsp&nbsp<input type="text" name="nEmail" value=""><br/>
                年級&nbsp&nbsp
                <select name="nGrade">
                    <option value="1">一</option>
                    <option value="2">二</option>
                    <option value="3">三</option>
                    <option value="4">四</option>
                    <option value="5">其他</option>
                </select>
        </ol>
        <p><hr><p>
        <h3>🎭 馬戲團設定</h3>  
        <ol>
            <li>如果你進入數位馬戲團，你覺得自己會是？（單選）<br/>
                <input type="radio" name="character" value="clown">🤡 小丑（帶來混亂與歡笑）<br/>
                <input type="radio" name="character" value="magician">🎩 魔術師（操控未知）<br/>
                <input type="radio" name="character" value="acrobat">🎪 雜技演員（突破極限）<br/>
                <input type="radio" name="character" value="audience">👁 觀眾（你真的只是觀察嗎？）<br/>
            <li>面對未知與混亂，你的反應是？（複選）<br/>
                <input type="checkbox" name="reaction" value="1">享受它
                <input type="checkbox" name="reaction" value="2">試圖控制
                <input type="checkbox" name="reaction" value="3">找出口
                <input type="checkbox" name="reaction" value="4">假裝沒事
                <input type="checkbox" name="reaction" value="5">……我本來就是混亂
            <li>你是否能接受「不完全合理」的任務挑戰？<br/>
                可以<input type="range" name="acceptability" value="">不太能
        </ol>
        <p><hr><p>
        <h3>🧩 參與動機</h3> 
        你為什麼想加入這個夏令營？<br/><textarea name="why" rows="5" cols="200"></textarea><br/><br/>
        你希望在這裡獲得什麼？<br/><textarea name="what" rows="5" cols="200"></textarea>
        <p>
        <center>
        <input type="submit">
        <input type="reset">
        </center>
        

            
</body>
