document.getElementById("loginForm").addEventListener("submit", function(event) {
    event.preventDefault(); // منع إعادة تحميل الصفحة

    let formData = new FormData(this);

    fetch("login.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            window.location.href = data.redirect; // إعادة التوجيه إلى لوحة التحكم
        } else {
            alert(data.message); // عرض رسالة خطأ
        }
    })
    .catch(error => console.error("Error:", error));
});
