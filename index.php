<?php
session_start();
include 'config.php'; // Include your database configuration

// Fetch hero slides
$hero_slides = [];
$sql_slides = "SELECT * FROM hero_slides WHERE deleted_at IS NULL ORDER BY media_type DESC, created_at DESC";
$result_slides = $conn->query($sql_slides);
if ($result_slides->num_rows > 0) {
    $video_count = 0;
    $image_count = 0;
    while($row = $result_slides->fetch_assoc()) {
        if ($row['media_type'] === 'video' && $video_count < 1) {
            $hero_slides[] = $row;
            $video_count++;
        } elseif ($row['media_type'] === 'image' && $image_count < 4) {
            $hero_slides[] = $row;
            $image_count++;
        }
    }
}

// Fetch THE LATEST unrated reservation for the logged-in user
$unrated_reservation = null;
$show_modal_on_load = false;
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    // Check if the session flag is set to show the modal
    if (isset($_SESSION['show_rating_modal']) && $_SESSION['show_rating_modal'] === true) {
        $user_id = $_SESSION['user_id'];
        $sql_unrated = "SELECT r.reservation_id, r.res_date FROM reservations r LEFT JOIN testimonials t ON r.reservation_id = t.reservation_id WHERE r.user_id = ? AND t.id IS NULL AND r.status = 'Confirmed' AND r.deleted_at IS NULL ORDER BY r.res_date DESC LIMIT 1";
        
        if ($stmt = $conn->prepare($sql_unrated)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $unrated_reservation = $result->fetch_assoc();
                $show_modal_on_load = true; // We have a reservation to rate, so we should show the modal
            }
            // Unset the session variable so the modal doesn't pop up on every page refresh
            $_SESSION['show_rating_modal'] = false;
        }
    }
}


// Fetch featured testimonials
$featured_testimonials = [];
$sql_testimonials = "SELECT t.*, u.username, u.avatar FROM testimonials t JOIN users u ON t.user_id = u.user_id WHERE t.is_featured = 1 AND t.deleted_at IS NULL ORDER BY t.created_at DESC LIMIT 3";
$result_testimonials = $conn->query($sql_testimonials);
if ($result_testimonials->num_rows > 0) {
    while ($row = $result_testimonials->fetch_assoc()) {
        $featured_testimonials[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico</title>
    <link rel="stylesheet" href="CSS/main.css">
    <link rel="stylesheet" href="CSS/dark-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* FIX: Override header shrinking animation on this page */
        .main-header,
        .main-header.header-scrolled {
            height: 90px !important;
            transition: none !important;
        }

        .main-header .logo-main-line span,
        .main-header.header-scrolled .logo-main-line span {
            font-size: 32px !important;
            transition: none !important;
        }
        /* --- END OF BUG FIX --- */

        /* --- INLINED RESPONSIVE HERO SECTION STYLES --- */
        .hero-section .hero-overlay {
            justify-content: flex-start;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.6); 
            padding-inline: clamp(1.5rem, 10vw, 8rem);
            padding-block: 2rem;
            box-sizing: border-box;
        }
        
        .hero-text-container { text-align: left; max-width: 650px; }
        .hero-text-container h1 { font-family: 'Madimi One', sans-serif; margin-bottom: 15px; color: #FFD700; line-height: 1.2; font-weight: 700; text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.7); font-size: clamp(2.2rem, 7vw + 1rem, 4.5rem); word-wrap: break-word; hyphens: auto; }
        .hero-text-container p { margin-bottom: 25px; max-width: 500px; color: #FFFFFF; text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.7); font-size: clamp(0.9rem, 2vw + 0.5rem, 1.2rem); }
        .hero-buttons { display: flex; gap: 15px; }
        .hero-buttons .btn { border-radius: 8px; font-weight: bold; padding: 14px 20px; font-size: 1em; text-transform: none; transition: all 0.3s ease; width: 180px; display: inline-flex; justify-content: center; align-items: center; margin-top: 0; text-decoration: none; }
        .hero-buttons .btn.btn-outline-white { background-color: transparent; color: #fff; border: 1px solid #fff; }
        .hero-buttons .btn.btn-secondary { background-color: #2a2a2a; color: #ffffff; border: 1px solid #b0b0b0; }
        .hero-buttons .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .hero-buttons .btn.btn-outline-white:hover { background-color: rgba(255, 255, 255, 0.15); border-color: #fff; }
        .hero-buttons .btn.btn-secondary:hover { background-color: #404040; border-color: #ffffff; }
        .section-heading-v2 .main-title { white-space: nowrap; }

        /* --- INLINED RESPONSIVE SECTION HEADING & SLIDER STYLES --- */
        .guest-testimonials-section h2 { font-size: clamp(2rem, 5vw + 1rem, 2.8rem); }
        .section-heading-v2 .sub-title { font-size: clamp(1.5rem, 4vw + 0.5rem, 2.2rem); }
        .section-heading-v2 .main-title { font-size: clamp(1.8rem, 5vw + 1rem, 2.8rem); }

        .hero-bg-video { position: absolute; top: 50%; left: 50%; width: 100%; height: 100%; object-fit: cover; transform: translate(-50%, -50%); }

        /* --- MODAL RATING FORM STYLES --- */
        #ratingModal .modal-content { max-width: 550px; }
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 5px;
            margin-bottom: 15px;
        }
        .star-rating input { display: none; }
        .star-rating label {
            font-size: 2.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #FFD700;
        }
        #ratingModal .modal-form-container { padding: 35px 30px; }
        #ratingModal .modal-title { text-align: center; margin-bottom: 25px; }
        #ratingModal .modal-form { width: 100%; }
        
        .slider-wrapper { display: grid; grid-template-columns: repeat(3, 1fr); gap: 29px; }

        @media (max-width: 768px) {
            .hero-section { height: 80vh; }
            .hero-overlay { justify-content: center; }
            .hero-text-container { text-align: center; display: flex; flex-direction: column; align-items: center; }
            .hero-buttons { flex-direction: column; align-items: center; width: 80%; max-width: 300px; }
            .hero-buttons .btn { width: 100%; text-align: center; }
            .slider-wrapper { display: flex; }
            .slider-btn { display: none !important; /* HIDE arrow buttons on mobile */ }
        }
        
        @media (max-width: 480px) {
             .section-heading-v2 .main-title { white-space: normal; padding: 0; }
             .section-heading-v2 .line { display: none; }
        }

        /* Added Animation CSS */
        @import url("https://fonts.googleapis.com/css?family=Signika+Negative:300,400&display=swap");
        *,
        *:before,
        *:after {
          box-sizing: border-box;
          position: relative;
          /* BUG FIX: Removed letter-spacing from universal selector */
        }
        h1 {
          font-size: 40px;
          line-height: 1.2;
          margin: 0;
        }
        .revealUp {
          opacity: 0;
          visibility: hidden;
          transform: translateY(20px);
          transition: opacity 1s, transform 1s, visibility 1s;
        }
        .revealUp.active {
          opacity: 1;
          visibility: visible;
          transform: translateY(0);
        }
        
        /* FIX: Make section heading backgrounds transparent */
        .section-heading-v2, .guest-testimonials-section h2 {
            background-color: transparent !important;
        }
    </style>
</head>

<body>

    <?php include 'partials/header.php'; ?>

    <section class="hero-section">
        <div class="slideshow-container">
            <?php if (!empty($hero_slides)): ?>
                <?php foreach ($hero_slides as $index => $slide): ?>
                    <div class="mySlides fade">
                        <?php if ($slide['media_type'] === 'video' && !empty($slide['video_path'])): ?>
                            <video autoplay muted playsinline class="hero-bg-video">
                                <source src="<?php echo htmlspecialchars($slide['video_path']); ?>" type="video/mp4">
                            </video>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($slide['image_path']); ?>" alt="Hero Image" class="hero-bg-image">
                        <?php endif; ?>
                        
                        <div class="hero-overlay">
                            <div class="hero-text-container">
                                <?php if (!empty($slide['title'])): ?>
                                    <h1><?php echo htmlspecialchars($slide['title']); ?></h1>
                                <?php else: ?>
                                    <h1>Experience Authentic Flavors at Tavern Publico</h1>
                                <?php endif; ?>
                                
                                <?php if (!empty($slide['subtitle'])): ?>
                                    <p><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                                <?php else: ?>
                                    <p>Craft coffee, comfort food, and a welcoming atmosphere in the heart of the city.</p>
                                <?php endif; ?>
                                
                                <div class="hero-buttons">
                                    <a href="menu.php" class="btn btn-outline-white">View Menu</a>
                                    <?php
                                    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                                        echo '<a href="reserve.php" class="btn btn-secondary">Reserve Now</a>';
                                    } else {
                                        echo '<button class="btn btn-secondary signin-button">Reserve Now</button>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div style="text-align:center; position: absolute; bottom: 20px; width: 100%; z-index: 2;">
                    <?php foreach ($hero_slides as $index => $slide): ?>
                        <span class="dot" onclick="currentSlide(<?php echo $index + 1; ?>)"></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                 <div class="mySlides fade" style="display:block;">
                    <img src="images/story.jpg" alt="Default Hero Image" class="hero-bg-image">
                    <div class="hero-overlay">
                        <div class="hero-text-container">
                            <h1>Experience Authentic Flavors at Tavern Publico</h1>
                            <p>Craft coffee, comfort food, and a welcoming atmosphere in the heart of the city.</p>
                            <div class="hero-buttons">
                                <a href="menu.php" class="btn btn-outline-white">View Menu</a>
                                <?php
                                if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                                    echo '<a href="reserve.php" class="btn btn-secondary">Reserve Now</a>';
                                } else {
                                    echo '<button class="btn btn-secondary signin-button">Reserve Now</button>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="specialties-section common-padding">
    <div class="container">
        <div class="section-heading-v2 revealUp">
            <div class="sub-title">Freshly Taste</div>
            <div class="title-with-lines">
                <div class="line"></div>
                <h2 class="main-title">Our Specialties</h2>
                <div class="line"></div>
            </div>
        </div>
            <div class="slider-container">
                <div class="slider-wrapper">
                    <?php 
                    $sql_specialties = "SELECT * FROM menu WHERE category = 'Specialty' AND deleted_at IS NULL ORDER BY RAND() LIMIT 3";
                    $result_specialties = $conn->query($sql_specialties);
                    if ($result_specialties->num_rows > 0) {
                        while ($row = $result_specialties->fetch_assoc()) {
                            echo '<div class="slider-item"><div class="specialty-card">';
                            echo '<img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['name']) . '">';
                            echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
                            $description = htmlspecialchars($row['description']);
                            $words = explode(' ', $description);
                            if (count($words) > 20) {
                                $description = implode(' ', array_slice($words, 0, 20)) . '...';
                            }
                            echo '<p>' . $description . '</p>';
                            echo '<div class="price-arrow"><span class="price">₱' . number_format($row['price'], 2) . '</span></div>';
                            echo '</div></div>';
                        }
                    }
                    ?>
                </div>
                <button class="slider-btn prev-btn">&lt;</button>
                <button class="slider-btn next-btn">&gt;</button>
            </div>
            <a href="menu.php" class="btn btn-secondary">View Full Menu</a>
        </div>
    </section>

    <section class="our-story-section common-padding">
    <div class="container">
        <div class="section-heading-v2 revealUp">
            <div class="sub-title">A Rich Heritage</div>
            <div class="title-with-lines">
                <div class="line"></div>
                <h2 class="main-title">Our Story</h2>
                <div class="line"></div>
            </div>
        </div>
            <div class="story-content">
    <div class="story-image"><img src="images/story.jpg" alt="Our Story Image"></div>
    <div class="story-text">
        <p>Founded in 2024, Tavern Publico was born from a passion for bringing together exceptional craft food and drinks in a welcoming environment. Our chefs use locally-sourced ingredients to create memorable dishes that honor tradition while embracing innovation.</p>
        <p>Every visit to Tavern Publico is an opportunity to experience the warmth of our hospitality and the quality of our cuisine.</p>
        <a href="about.php" class="btn btn-outline-dark">Learn More About Us</a>
    </div>
</div>
        </div>
    </section>

    <section class="upcoming-events-section common-padding">
        <div class="container">
            <div class="section-heading-v2 revealUp">
                <div class="sub-title">Don't Miss Out</div>
                <div class="title-with-lines">
                    <div class="line"></div>
                    <h2 class="main-title">Upcoming Events</h2>
                    <div class="line"></div>
                </div>
            </div>
            <div class="slider-container">
                <div class="slider-wrapper">
                    <?php 
                    $sql_events = "SELECT * FROM events WHERE deleted_at IS NULL ORDER BY date DESC LIMIT 3";
                    $result_events = $conn->query($sql_events);
                    if ($result_events->num_rows > 0) {
                        while ($row = $result_events->fetch_assoc()) {
                            $start_date_formatted = date("l, F j, Y", strtotime($row['date']));
                            $date_display = $start_date_formatted;
                            if (!empty($row['end_date'])) {
                                $end_date_formatted = date("l, F j, Y", strtotime($row['end_date']));
                                if ($start_date_formatted !== $end_date_formatted) {
                                    $date_display .= " - " . $end_date_formatted;
                                }
                            }
                            echo '<div class="slider-item">
                                    <div class="event-card">
                                        <img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '">
                                        <span class="event-date">' . htmlspecialchars($date_display) . '</span>
                                        <h3>' . htmlspecialchars($row['title']) . '</h3>
                                        <p>' . substr(htmlspecialchars($row['description']), 0, 100) . '...</p>
                                    </div>
                                  </div>';
                        }
                    }
                    ?>
                </div>
                <button class="slider-btn prev-btn">&lt;</button>
                <button class="slider-btn next-btn">&gt;</button>
            </div>
            <a href="events.php" class="btn btn-secondary">View All Events</a>
        </div>
    </section>

    <section class="guest-testimonials-section common-padding">
        <div class="container">
            <h2 class="revealUp">What Our Guests Say</h2>
            <div class="slider-container">
                <div class="slider-wrapper">
                    <?php if (!empty($featured_testimonials)): ?>
                        <?php foreach ($featured_testimonials as $testimonial): ?>
                            <div class="slider-item"><div class="testimonial-card">
                                <div class="stars"><?php echo str_repeat('★', $testimonial['rating']) . str_repeat('☆', 5 - $testimonial['rating']); ?></div>
                                <p>"<?php echo htmlspecialchars($testimonial['comment']); ?>"</p>
                                <div class="guest-info">
                                    <?php $avatar_path = !empty($testimonial['avatar']) && file_exists($testimonial['avatar']) ? $testimonial['avatar'] : 'images/default_avatar.png'; ?>
                                    <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="<?php echo htmlspecialchars($testimonial['username']); ?>">
                                    <div class="guest-details"><span class="guest-name"><?php echo htmlspecialchars($testimonial['username']); ?></span></div>
                                </div>
                            </div></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button class="slider-btn prev-btn">&lt;</button>
                <button class="slider-btn next-btn">&gt;</button>
            </div>
        </div>
    </section>

    <section class="call-to-action-section">
        <div class="container">
            <div class="cta-content"><h2>Ready to Experience Tavern Publico?</h2><p>Join us for an unforgettable dining experience. Whether you're planning a romantic dinner, family gathering, or just want to enjoy great food and drinks, we're here to serve you.</p><div class="cta-buttons"><a href="reserve.php" class="btn btn-outline-white">Reserve a Table</a><a href="menu.php" class="btn btn-outline-white">View Our Menu</a><a href="contact.php" class="btn btn-outline-white">Contact Us</a></div></div>
        </div>
    </section>

    <?php if ($show_modal_on_load && !empty($unrated_reservation)): ?>
    <div id="ratingModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <div class="modal-form-container">
                <h2 class="modal-title">Rate Your Recent Visit</h2>
                <form id="ratingForm" class="modal-form">
                    <div class="form-group" style="text-align: left; font-size: 1.1em; margin-bottom: 20px;">
                        <p>Please rate your visit on: <strong><?php echo htmlspecialchars($unrated_reservation['res_date']); ?></strong></p>
                        <input type="hidden" name="reservation_id" value="<?php echo $unrated_reservation['reservation_id']; ?>">
                    </div>
                    <div class="form-group" style="text-align: left;">
                        <label>Your Rating:</label>
                        <div class="star-rating">
                            <input type="radio" id="5-stars" name="rating" value="5" required /><label for="5-stars" class="star">★</label>
                            <input type="radio" id="4-stars" name="rating" value="4" /><label for="4-stars" class="star">★</label>
                            <input type="radio" id="3-stars" name="rating" value="3" /><label for="3-stars" class="star">★</label>
                            <input type="radio" id="2-stars" name="rating" value="2" /><label for="2-stars" class="star">★</label>
                            <input type="radio" id="1-star" name="rating" value="1" /><label for="1-star" class="star">★</label>
                        </div>
                    </div>
                    <div class="form-group" style="text-align: left;">
                        <label for="comment">Leave a comment:</label>
                        <textarea name="comment" id="comment" rows="4" placeholder="Tell us about your experience..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 1em;" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary modal-btn">Submit Rating</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'partials/footer.php'; ?>
    <?php include 'partials/Signin-Signup.php'; ?>
    <script src="JS/theme-switcher.js"></script>
    
    <script>
        // SCRIPT FOR SLIDERS, HERO SECTION, AND RATING FORM
        document.addEventListener('DOMContentLoaded', () => {
    
            // --- CUSTOM SLIDER LOGIC (with SWIPE functionality) ---
            function initializeCustomSliders() {
                document.querySelectorAll('.slider-container').forEach(container => {
                    const wrapper = container.querySelector('.slider-wrapper');
                    const slides = Array.from(container.querySelectorAll('.slider-item'));
                    const prevBtn = container.querySelector('.prev-btn');
                    const nextBtn = container.querySelector('.next-btn');
                    
                    if (!wrapper || slides.length <= 1) {
                        if(prevBtn) prevBtn.style.display = 'none';
                        if(nextBtn) nextBtn.style.display = 'none';
                        return;
                    }

                    let currentIndex = 0;
                    const slideCount = slides.length;
                    let touchstartX = 0;
                    let touchendX = 0;

                    function updateArrows() {
                        if (prevBtn && nextBtn) {
                            prevBtn.disabled = currentIndex === 0;
                            nextBtn.disabled = currentIndex >= slideCount - 1;
                        }
                    }

                    function goToSlide(index) {
                        if (window.innerWidth > 768) {
                            wrapper.style.transform = 'translateX(0)';
                            if (prevBtn && nextBtn) {
                                prevBtn.style.display = 'none';
                                nextBtn.style.display = 'none';
                            }
                            return;
                        }
                        
                         if (prevBtn && nextBtn) {
                            prevBtn.style.display = 'flex';
                            nextBtn.style.display = 'flex';
                        }

                        currentIndex = Math.max(0, Math.min(index, slideCount - 1));

                        const scrollAmount = slides[currentIndex].offsetLeft;
                        wrapper.style.transform = `translateX(-${scrollAmount}px)`;
                        updateArrows();
                    }
                    
                    function handleGesture() {
                        if (touchendX < touchstartX - 50) { // Swiped left
                            goToSlide(currentIndex + 1);
                        }
                        if (touchendX > touchstartX + 50) { // Swiped right
                            goToSlide(currentIndex - 1);
                        }
                    }
                    
                    wrapper.addEventListener('touchstart', e => {
                        touchstartX = e.changedTouches[0].screenX;
                    }, { passive: true });

                    wrapper.addEventListener('touchend', e => {
                        touchendX = e.changedTouches[0].screenX;
                        handleGesture();
                    });

                    if (nextBtn) {
                        nextBtn.addEventListener('click', () => goToSlide(currentIndex + 1));
                    }
                    
                    if (prevBtn) {
                        prevBtn.addEventListener('click', () => goToSlide(currentIndex - 1));
                    }
                    
                    window.addEventListener('resize', () => goToSlide(currentIndex));
                    goToSlide(0);
                });
            }

            initializeCustomSliders();

            // --- HERO SLIDESHOW LOGIC ---
            const slides = document.querySelectorAll(".slideshow-container .mySlides");
            const dots = document.querySelectorAll(".slideshow-container .dot");
            let slideIndex = 0;
            let slideInterval;

            if (slides.length > 1) { 
                function moveToSlide(n) {
                    clearInterval(slideInterval);
                    const oldVideo = slides[slideIndex]?.querySelector("video.hero-bg-video");
                    if (oldVideo) {
                        oldVideo.pause();
                        oldVideo.onended = null;
                    }

                    slides.forEach(slide => slide.style.display = "none");
                    dots.forEach(dot => dot.classList.remove("active"));
                    
                    slideIndex = n >= slides.length ? 0 : (n < 0 ? slides.length - 1 : n);

                    const currentSlide = slides[slideIndex];
                    if(dots[slideIndex]) dots[slideIndex].classList.add("active");
                    currentSlide.style.display = "block";
                    
                    const newVideo = currentSlide.querySelector("video.hero-bg-video");
                    
                    if (newVideo) {
                        newVideo.currentTime = 0;
                        newVideo.play().catch(error => console.error("Video autoplay failed.", error));
                        newVideo.onended = () => moveToSlide(slideIndex + 1);
                    } else {
                        slideInterval = setInterval(() => moveToSlide(slideIndex + 1), 5000); 
                    }
                }
                
                window.currentSlide = (n) => moveToSlide(n - 1);

                moveToSlide(0);
            } else if (slides.length === 1) {
                slides[0].style.display = 'block';
            }
        
            // --- RATING FORM LOGIC ---
            const ratingModal = document.getElementById('ratingModal');
            const showModalOnLoad = <?php echo json_encode($show_modal_on_load); ?>;

            if (ratingModal && showModalOnLoad) {
                const closeBtn = ratingModal.querySelector('.close-button');
                const ratingForm = document.getElementById('ratingForm');

                const closeRatingModal = () => {
                    ratingModal.style.display = 'none';
                };

                closeBtn.addEventListener('click', closeRatingModal);
                
                ratingForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    fetch('submit_rating.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            // Using the custom modal alert from Signin-Signup.php
                            if(typeof showAlert === 'function') {
                                showAlert(data.success ? 'Success' : 'Error', data.message);
                            } else {
                                alert(data.message); // Fallback to old alert
                            }

                            if (data.success) {
                                closeRatingModal();
                            }
                        });
                });

                ratingModal.style.display = 'flex';
            }

            // --- ANIMATION ON SCROLL ---
            const revealElements = document.querySelectorAll('.revealUp');

            const revealObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                    }
                });
            }, {
                threshold: 0.1 
            });

            revealElements.forEach(el => {
                revealObserver.observe(el);
            });

            // --- AUTO-SCROLL ON MOBILE ---
            function initAutoScroll() {
                if (window.innerWidth <= 768) { // Only run on mobile
                    const sections = Array.from(document.querySelectorAll('main > section'));
                    let currentSectionIndex = 0;
                    let scrollInterval;
                    let isUserScrolling = false;

                    const startScrolling = () => {
                        if (isUserScrolling) return; // Don't start if user is still interacting
                        
                        // Clear any existing interval before starting a new one
                        clearInterval(scrollInterval); 
                        
                        scrollInterval = setInterval(() => {
                            currentSectionIndex = (currentSectionIndex + 1) % sections.length;
                            const nextSection = sections[currentSectionIndex];
                            
                            nextSection.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });

                        }, 4000); // Scroll every 4 seconds
                    };

                    const stopScrolling = () => {
                        clearInterval(scrollInterval);
                    };

                    // Start scrolling initially
                    startScrolling();

                    // Pause auto-scrolling if the user manually scrolls or touches the screen
                    let userInteractionTimeout;
                    const handleUserInteraction = () => {
                        isUserScrolling = true;
                        stopScrolling();
                        clearTimeout(userInteractionTimeout);
                        userInteractionTimeout = setTimeout(() => {
                            isUserScrolling = false;
                            startScrolling(); // Restart after 10 seconds of inactivity
                        }, 10000);
                    };
                    
                    window.addEventListener('wheel', handleUserInteraction, { passive: true });
                    window.addEventListener('touchstart', handleUserInteraction, { passive: true });
                }
            }

            // Call the function to set it up
            initAutoScroll();
        });
    </script>
</body>

</html>