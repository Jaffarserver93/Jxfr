 <p align="center">
  <a href="https://github.com/PacaHat/Anipaca">
    <img 
      src="https://raw.githubusercontent.com/PacaHat/Anipaca/refs/heads/main/public/logo/Untitled255_20241231223556.png" 
      alt="anipaca_mascot" 
      width="275" 
      height="275"
      decoding="async"
      fetchpriority="high"
    /> 
  </a>
</p> 


# <p align="center"> <img src="public/logo/logo.png?v=0.1" alt="Logo" width="50%" height="50%"></p>

<p align="center">
  <div align="center">
    <h3><s>(<a href="https://hianime.to/">Hianime.to</a> Clone)</s> Anipaca - Watch High Quality Anime Online</h3>
    <a href="https://discord.gg/aVvqx77RGs">
      <img src="https://img.shields.io/discord/1012901585896087652?label=&logo=discord&color=5460e6&style=flat-square&labelColor=2b2f35">
    </a>
 <a href="https://github.com/PacaHat/Anipaca/graphs/contributors">
      <img src="https://img.shields.io/github/contributors/PacaHat/Anipaca">
    </a>
     </a>
 <a href="https://github.com/PacaHat/Anipaca/forks">
      <img src="https://img.shields.io/github/forks/PacaHat/Anipaca">
    </a>
     <a href="https://github.com/PacaHat/Anipaca/stargazers">
      <img src="https://img.shields.io/github/stars/PacaHat/Anipaca">
    </a>
    <a href="https://github.com/PacaHat/Anipaca/issues">
      <img src="https://img.shields.io/github/issues/PacaHat/Anipaca">
    </a>
  </div>
  <hr />
</p>

> [!IMPORTANT]
>
> 1. This website is just an unofficial clone of [hianime.to](https://hianime.to) But both share same database.
> 2. The content that this website provides is not mine, nor is it hosted by me. These belong to their respective owners. This website just demonstrates how to build an ANIME WEBSITE .
> 3. Do not use this for commercial purposes. If you place ads on your site, I will personally file a DMCA complaint.
> 4. This is just for educational purposes 

<!-- TABLE OF CONTENTS -->
<details>
  <summary>Table of Contents</summary>
  <ol>
    <li>
      <a href="#about-the-project">About The Project</a>
      <ul>
        <li><a href="#built-with">Built With</a></li>
      </ul>
    </li>
    <li>
      <a href="#getting-started">Getting Started</a>
      <ul>
        <li><a href="#installation">Installation</a></li>
      </ul>
    </li>
    <li><a href="#usage">Usage</a></li>
    <li><a href="#roadmap">Roadmap</a></li>
    <li><a href="#contributing">Contributing</a></li>
  </ol>
</details>



<!-- ABOUT THE PROJECT -->
## About The Project

![Product Name Screen Shot](https://raw.githubusercontent.com/PacaHat/Anipaca/refs/heads/main/public/images/banner.png "Product Screenshot")


<details>
  <summary><strong>Anipaca</strong> - Click to expand</summary>
  <p>
     **Anipaca** is an open-source anime streaming website that provides a high-quality anime viewing experience. Built on top of the HiAnimeAPI, this PHP application offers a range of features designed for anime enthusiasts.
  </p>
  <h3>Why You Should Use Anipaca</h3>
  <ul>
    <li><strong>Ad-Free Experience</strong>: Enjoy watching anime without annoying video ads.</li>
    <li><strong>High-Quality Streaming</strong>: Stream your favorite shows in 1080p, 720p, 480p, and 360p.</li>
    <li><strong>Device Compatibility</strong>: Access the platform on PCs, laptops, tablets, mobile devices, and smart TVs.</li>
    <li><strong>Extensive Browsing Options</strong>: Easily browse, search, and watch anime based on genres, seasons, and more.</li>
    <li><strong>Future Integration</strong>: Potential integration with Anilist for enhanced features.</li>
  </ul>
  <p>Explore Anipaca and elevate your anime streaming experience!</p>
</details>


## Contributing
Thank you for considering contributing to the AniPaca! The contribution guide can be found in the [Contribution guide.](https://github.com/PacaHat/Anipaca/blob/main/contribution/GUIDE.md).

<!-- GETTING STARTED -->
## Getting Started

This is an example of how you may give instructions on setting up your project on cPanle.
To get a website running up follow these simple example steps.


### API That you need

| API Name     | Deploy Link                                                                                              | Example API Link                                     |
|--------------|----------------------------------------------------------------------------------------------------------|-----------------------------------------------------|
| Main API   | [![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https://github.com/PacaHat/zen-api)          | `GET https://zen-api-brown.vercel.app/`         |
| M3U8 Proxy    | [![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https://github.com/PacaHat/shrina-proxy) | `GET https://m3u8proxy.vercel.app/proxy?url=`     |





## Installation

1. **Clone or Download the repository**:
   ```bash
   git clone https://github.com/PacaHat/Anipaca.git
   ```

2. **Set up the database**:
   - Import the provided SQL file into your MySQL database.
   - Update the database connection details in `_config.php`.

3.  **Set up the database**:
   ```bash
<?php 

$conn = new mysqli("HOSTNAME", "USERNAME", "PASSWORD", "DATABASE");

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed.");
}

$websiteTitle = "AniPaca";
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$websiteUrl = "{$protocol}://{$_SERVER['SERVER_NAME']}";
$websiteLogo = $websiteUrl . "/public/logo/logo.png";
$contactEmail = "@gmail.com";

$version = "2.1";

$discord = "https://dcd.gg/anipaca";
$github = "https://github.com/PacaHat";
$telegram = "https://t.me/anipaca";
$instagram = "https://www.instagram.com/pxr15_"; 

// all the api you need
$zpi = "https://your-hosted-api.com/api"; //https://github.com/PacaHat/zen-api
$proxy = $websiteUrl . "/src/ajax/proxy.php?url=";

//If you want faster loading speed
//$proxy = "https://your-hosted-proxy.com/proxy?url="; //https://github.com/PacaHat/shrina-proxy



$banner = $websiteUrl . "/public/images/banner.png";

    

    
 ```


<!-- ROADMAP -->
## Roadmap

- [x] Add Comment section
- [ ] Improve Comment section
- [x] Add PHP routing system to avoid htaccess error
- [x] Add Multiple video sources
- [ ] Anime download
- [ ] Admin pannel to manage and monitor site
- [ ] **Need more features? Create a request on our Discord server!** [Join here](https://discord.gg/aVvqx77RGs)


<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- CONTRIBUTING -->

## Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue with the tag "enhancement".
Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request


<p align="right">(<a href="#readme-top">back to top</a>)</p>





# Disclaimer: Educational Purpose Only  

This repository and its contents were created solely for **educational purposes** and to showcase technical skills in web development.  

## Important Notes:  
- The author of this project does **not** promote, condone, or support any illegal activity, including but not limited to piracy or copyright infringement.  
- The project was developed as an example of web application development and should **only** be used in lawful and ethical ways.  

## Unauthorized Use  
The code and associated materials in this project are the intellectual property of the author. Any unauthorized use, modification, or monetization of this work is strictly prohibited.  

If you suspect that this code is being used inappropriately, please report the misuse to the author via **[raisulentertainment@gmail.com](mailto:raisulentertainment@gmail.com)**.  

## Legal Action  
The author reserves the right to pursue legal action against individuals or organizations found misusing this project or its contents.  

---

*This project is privately maintained and intended only for the developer's personal educational growth.*  

**Date:** 19 January 2025  
**Author:** Raisul Rahat


# Jxfr
