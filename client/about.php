<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PartsMart - About Us</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&family=Poppins:wght@300;400;600&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="../styles/navbar.css">
  <link rel="stylesheet" href="../styles/footer.css">
  <style>
    body,
    html {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      overflow-x: hidden;
      font-family: Poppins, sans-serif;
    }

    body::-webkit-scrollbar {
      display: none
    }

    .about-container {
      width: 80%;
      margin: 13vh auto 5vh;
      font-family: Poppins, sans-serif;
    }

    .about-hero {
      text-align: center;
      margin-bottom: 4rem;
    }

    .about-hero h1 {
      font-size: 3rem;
      margin-bottom: 1.5rem;
      color: #333;
    }

    .about-hero p {
      font-size: 1.2rem;
      color: #666;
      max-width: 800px;
      margin: 0 auto;
    }

    .about-section {
      margin: 4rem 0;
      display: flex;
      gap: 4rem;
      align-items: center;
    }

    .about-section img {
      width: 400px;
      height: 300px;
      object-fit: cover;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .about-content {
      flex: 1;
    }

    .about-content h2 {
      font-size: 2rem;
      margin-bottom: 1.5rem;
      color: #333;
    }

    .about-content p {
      font-size: 1.1rem;
      line-height: 1.6;
      color: #555;
      margin-bottom: 1rem;
    }

    .values-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2rem;
      margin: 4rem 0;
    }

    .value-card {
      background: white;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      text-align: center;
      transition: transform 0.3s ease;
    }

    .value-card:hover {
      transform: translateY(-10px);
    }

    .value-card h3 {
      font-size: 1.5rem;
      margin-bottom: 1rem;
      color: #333;
    }

    .value-card p {
      font-size: 1rem;
      color: #666;
    }

    .elegant-quote {
      text-align: center;
      background: #fff;
      color: #181818;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      padding: 3rem;
      margin: 4rem 0;
      transform: scale(1);
      transition: transform 0.3s ease;
    }

    .elegant-quote:hover {
      transform: scale(1.01);
    }

    .elegant-quote p {
      font-size: 1.5rem;
      font-weight: 600;
      letter-spacing: 1px;
      line-height: 1.4;
    }
  </style>
</head>

<body>
  <?php include 'header.php' ?>

  <div class="about-container">
    <div class="about-hero">
      <h1>About PartsMart</h1>
      <p>Building the future of bulk parts supply, one order at a time.</p>
    </div>

    <section class="about-section">
      <img src="../assets/products/mainimage.jpg" alt="PartsMart Warehouse">
      <div class="about-content">
        <h2>Our Story</h2>
        <p>PartsMart emerged from a simple yet powerful idea: to revolutionize the way businesses
          source their bulk parts and hardware supplies. We recognized the challenges faced by contractors,
          manufacturers, and retailers in finding reliable, quality parts at competitive prices.</p>
        <p>Today, we serve thousands of businesses across the country, providing them with premium hardware solutions
          and unmatched customer service. Our commitment to quality, efficiency, and innovation has made us a trusted
          partner in the industry.</p>
      </div>
    </section>

    <div class="elegant-quote">
      <p>"Quality is not just our promise, it's our foundation."</p>
    </div>

    <div class="values-grid">
      <div class="value-card">
        <h3>Quality First</h3>
        <p>We source only the highest quality materials and products, ensuring durability and reliability in every
          piece.</p>
      </div>
      <div class="value-card">
        <h3>Customer Success</h3>
        <p>Your success is our success. We're committed to providing the support and solutions you need to grow.</p>
      </div>
      <div class="value-card">
        <h3>Innovation</h3>
        <p>We continuously evolve our products and services to meet the changing needs of our customers.</p>
      </div>
    </div>

    <section class="about-section">
      <div class="about-content">
        <h2>Our Commitment</h2>
        <p>At PartsMart, we believe in building lasting relationships with our customers through transparency,
          reliability, and exceptional service. Our team of experts works tirelessly to ensure that every order meets
          our high standards of quality.</p>
        <p>We're more than just a supplier - we're your partner in growth. Whether you're a small contractor or a large
          manufacturer, we're here to support your success with competitive prices, reliable delivery, and outstanding
          customer service.</p>
      </div>
      <img src="../assets/products/wrench.webp" alt="Professional Tools">
    </section>
  </div>

  <?php include 'footer.php' ?>
</body>

</html>