document.addEventListener('DOMContentLoaded', () => {
    const carousels = document.querySelectorAll('.testimonial-section');

    carousels.forEach(carousel => {
        // --- Scroll Logic ---
        const container = carousel.querySelector('.reviews-container');
        const scrollLeftBtn = carousel.querySelector('.custom-scroll-left');
        const scrollRightBtn = carousel.querySelector('.custom-scroll-right');

        if (container && scrollLeftBtn && scrollRightBtn) {
            const scrollCarousel = (direction) => {
                const card = container.querySelector('.review-card');
                if (!card) return;
                
                // Get dynamic measurements
                const cardStyle = window.getComputedStyle(card);
                const containerStyle = window.getComputedStyle(container);
                const cardWidth = parseInt(cardStyle.width, 10);
                const cardGap = parseInt(containerStyle.gap, 10) || 16;
                
                // Calculate scroll amount (Card width + Gap)
                const scrollAmount = (cardWidth + cardGap) * direction;
                
                container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            };
            
            scrollRightBtn.addEventListener('click', () => scrollCarousel(1));
            scrollLeftBtn.addEventListener('click', () => scrollCarousel(-1));
        }

        // --- Read More Logic ---
        const initializeReadMore = () => {
            const cards = carousel.querySelectorAll('.review-card-body');
            cards.forEach(card => {
                const text = card.querySelector('.review-text');
                const readMore = card.querySelector('.read-more');
                if (!text || !readMore) return;

                // Get the computed, collapsed max-height from the CSS (e.g., '4.5rem' -> 72px)
                const computedStyle = window.getComputedStyle(text);
                const collapsedHeight = parseInt(computedStyle.maxHeight, 10);

                // Check if the *actual* text height is taller than the *collapsed* height
                if (text.scrollHeight > collapsedHeight + 2) { // +2 buffer for rounding
                    readMore.classList.add('show');
                    readMore.textContent = 'Read more';
                } else {
                    readMore.classList.remove('show');
                }
            });
        };

        // This function handles the click event for Read More
        carousel.querySelectorAll('.review-card-body .read-more').forEach(link => {
            link.addEventListener('click', (e) => {
                const text = e.currentTarget.previousElementSibling; 
                if (!text || !text.classList.contains('review-text')) return;
                
                const isExpanded = text.classList.contains('expanded');
                
                if (isExpanded) {
                    // --- COLLAPSING ---
                    text.style.maxHeight = null; // Removes inline style, CSS transition takes over
                    e.currentTarget.textContent = 'Read more';
                } else {
                    // --- EXPANDING ---
                    text.style.maxHeight = text.scrollHeight + 'px'; // Animates to the exact height
                    e.currentTarget.textContent = 'Read less';
                }
                
                text.classList.toggle('expanded');
            });
        });

        // Run the visibility check *after* fonts/images are loaded (most reliable)
        window.addEventListener('load', initializeReadMore);
        window.addEventListener('resize', initializeReadMore);
    });
});