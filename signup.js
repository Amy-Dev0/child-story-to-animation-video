document.getElementById("signupForm").addEventListener("submit", async function (event) {
    event.preventDefault();

    const formData = new FormData(this);

    const response = await fetch("signup.php", {
        method: "POST",
        body: formData
    });

    const result = await response.json();
    
    if (result.status === "success") {
        alert("Account created successfully!");
        window.location.href = "login.html";  // توجيه المستخدم إلى صفحة تسجيل الدخول
    } else {
        alert(result.message);
    }
});
