# Campaign Website Design Implementation
## Kadri Obafemi Hamzat - Lagos State Governorship 2027

---

## 1. DESIGN OVERVIEW

### Project Vision
A clean, professional, and elegant campaign website that establishes credibility, communicates vision, and engages voters through a modern, accessible digital platform.

### Target Audience
- Lagos State voters (all demographics)
- Young professionals & students
- Families & community leaders
- Digital-savvy and traditional media consumers

### Key Objectives
- Build voter engagement and support
- Distribute campaign information and materials
- Drive social media following
- Register volunteers and supporters
- Showcase candidate's vision and achievements

---

## 2. DESIGN SYSTEM

### 2.1 Color Palette

**Primary Colors:**
- **Primary Blue**: `#003D82` - Trust, stability, governance
- **Accent Gold**: `#FFB81C` - Excellence, optimism, prosperity
- **White**: `#FFFFFF` - Clarity, cleanliness
- **Dark Gray**: `#1A1A1A` - Text, professional tone

**Secondary Colors:**
- **Light Blue**: `#E8F0F8` - Backgrounds, subtle highlights
- **Light Gray**: `#F5F5F5` - Section backgrounds, borders
- **Success Green**: `#27AE60` - Achievements, positive messaging
- **Dark Text**: `#333333` - Body text

**Usage:**
- Primary actions, headers, and key CTAs use Primary Blue
- Accent Gold highlights achievements, statistics, and important quotes
- White background with light gray sections for hierarchy
- High contrast ratios for accessibility (WCAG AA minimum)

### 2.2 Typography

**Font Family:** Inter, Segoe UI, sans-serif (modern, readable, professional)

**Font Scale:**
- **H1 (Hero Title)**: 56px / 67.2px line-height | Weight: 700 | Letter-spacing: -1.12px
- **H2 (Section Titles)**: 40px / 48px line-height | Weight: 700 | Letter-spacing: -0.8px
- **H3 (Subsection)**: 28px / 33.6px line-height | Weight: 600 | Letter-spacing: -0.56px
- **H4 (Component Title)**: 20px / 24px line-height | Weight: 600 | Letter-spacing: 0
- **Body Large**: 18px / 27px line-height | Weight: 400 | Letter-spacing: 0
- **Body Regular**: 16px / 24px line-height | Weight: 400 | Letter-spacing: 0
- **Body Small**: 14px / 21px line-height | Weight: 400 | Letter-spacing: 0
- **Label**: 12px / 18px line-height | Weight: 600 | Letter-spacing: 0.12px

### 2.3 Spacing System

**8px Grid Base:**
- **Micro**: 4px (internal spacing)
- **XS**: 8px
- **S**: 16px
- **M**: 24px
- **L**: 32px
- **XL**: 48px
- **2XL**: 64px
- **3XL**: 80px
- **4XL**: 96px

### 2.4 Elevation & Shadows

**Shadow 1** (subtle): `0px 2px 8px rgba(26, 26, 26, 0.06)`
**Shadow 2** (medium): `0px 4px 16px rgba(26, 26, 26, 0.1)`
**Shadow 3** (prominent): `0px 8px 32px rgba(26, 26, 26, 0.15)`

### 2.5 Border Radius

- **Buttons & Small elements**: 4px
- **Cards & Containers**: 8px
- **Large sections**: 12px
- **Images & Avatars**: 8px

---

## 3. HERO SLIDER COMPONENT

### 3.1 Specification

**Dimensions:**
- Full viewport width (100vw)
- Height: 100vh (desktop) / 70vh (tablet) / 60vh (mobile)
- Aspect ratio: 16:9 (images), responsive
- Positioned as the immediate page hero

**Structure:**
```
┌─────────────────────────────────────────────────────┐
│                    HERO SLIDER                      │
├─────────────────────────────────────────────────────┤
│                                                     │
│  ┌──────────────────────────────────────────────┐  │
│  │                                              │  │
│  │         [Image / Background]                │  │
│  │                                              │  │
│  │  [Overlay with Gradient]                    │  │
│  │                                              │  │
│  │  ┌────────────────────────────────────────┐ │  │
│  │  │ Tagline / Badge                        │ │  │
│  │  │ Main Headline                          │ │  │
│  │  │ Supporting Subtitle                    │ │  │
│  │  │ [CTA Button]                           │ │  │
│  │  └────────────────────────────────────────┘ │  │
│  │                                              │  │
│  │                    [Slide Indicators]       │  │
│  │  ← Previous    Pagination    Next →         │  │
│  │                                              │  │
│  └──────────────────────────────────────────────┘  │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### 3.2 Slider Specifications

**Carousel Settings:**
- **Number of Slides**: 5 key message slides
- **Auto-rotation**: 6 seconds per slide (with manual control pause)
- **Transition**: Fade (300ms duration)
- **Navigation**: Previous/Next arrows + Dot pagination
- **Loop**: Continuous loop enabled
- **Keyboard Support**: Arrow keys, Home, End

**Slide Content Areas:**

#### Slide 1: Vision & Leadership
- Image: Candidate professional portrait with Lagos skyline/community setting
- Tagline: "A NEW DIRECTION FOR LAGOS"
- Main Headline: "Visionary Leadership for Progressive Lagos"
- Subtitle: "Building a better future for all Lagos residents"
- CTA: "Learn Our Vision" (Primary Blue button)

#### Slide 2: Experience & Track Record
- Image: Candidate in governance/community setting, engaging with people
- Tagline: "PROVEN TRACK RECORD"
- Main Headline: "Experience That Delivers Results"
- Subtitle: "Years of dedicated service and tangible achievements for Lagos State"
- CTA: "View Achievements" (Primary Blue button)

#### Slide 3: Core Values
- Image: Diverse community/inclusion imagery, multicultural representation
- Tagline: "OUR CORE VALUES"
- Main Headline: "Integrity • Progress • Inclusivity"
- Subtitle: "Committed to serving every Lagosian with equity and excellence"
- CTA: "Our Values" (Primary Blue button)

#### Slide 4: Community Engagement
- Image: Campaign rally/community engagement/grassroots activities
- Tagline: "BE PART OF THE MOVEMENT"
- Main Headline: "Join the Movement for Better Lagos"
- Subtitle: "Together, we build a stronger, more prosperous state"
- CTA: "Get Involved" (Primary Blue button)

#### Slide 5: Vote 2027
- Image: Inspiring/futuristic Lagos imagery with forward momentum
- Tagline: "ELECTION DAY 2027"
- Main Headline: "Vote Kadri Obafemi Hamzat for Governor"
- Subtitle: "Saturday, March 11, 2027 - Vote for Progress and Prosperity"
- CTA: "Register to Vote" (Accent Gold button)

### 3.3 Visual Design Details

**Background:**
- High-quality, professionally shot images
- Lighting: Well-lit, optimistic, professional tone
- Composition: Candidate as focal point (positioned right or center)
- Image overlay: Dark gradient (0-60% opacity, top-to-bottom) for text readability

**Text Placement:**
- Content container: Left-aligned, 40% of slide width
- Padding: 48px-96px from edges (responsive)
- Z-index: Above background image and overlay
- Color: White text with dark overlay for contrast

**Tagline:**
- Position: Above main headline
- Style: Uppercase, 12px-14px, letter-spaced, semi-bold
- Color: Accent Gold with bottom border (2px)
- Animation: Fade-in on slide load (200ms delay)

**Main Headline (H1):**
- Font size: 52px-56px (desktop)
- Font weight: 700
- Color: White
- Animation: Slide-up with fade (300ms)
- Max-width: 500px
- Text shadow: Subtle (readability on varied backgrounds)

**Subtitle:**
- Font size: 18px (desktop)
- Font weight: 400
- Color: Light gray (#E8E8E8)
- Line height: 1.5
- Max-width: 450px
- Animation: Fade-in (500ms delay)

**Buttons:**
- Primary button: Blue background, white text, min-height 48px
- Secondary button: Gold background, dark text
- Hover: 5% brightness increase, slight lift
- Disabled: 50% opacity
- Animation: Fade-in (600ms delay), hover scale 1.05

### 3.4 Navigation Controls

**Previous/Next Arrows:**
- Position: Absolute, centered vertically, 24px from edges
- Size: 48x48px circles
- Icon: Chevron (24px)
- Color: White with 0.7 opacity
- Background: Transparent, hover to 0.9 opacity + slight blue tint
- Cursor: Pointer, smooth transition
- Accessibility: ARIA labels, keyboard support

**Pagination Dots:**
- Position: Bottom center, 24px from bottom
- Style: 8px circles with 4px spacing
- Active dot: Accent Gold (2x larger or different style)
- Inactive dots: White, 0.5 opacity
- Hover: 0.8 opacity
- Clickable: Direct jump to slide
- Accessibility: ARIA current, role="button"

**Keyboard Support:**
- Left Arrow: Previous slide
- Right Arrow: Next slide
- Home: First slide
- End: Last slide
- Space/Enter: Pause/Resume autoplay (optional)

**Autoplay Indicator:**
- Progress bar at top of hero
- Width: 100% → 0% over 6 seconds
- Color: Accent Gold
- Pauses on hover or interaction

### 3.5 Animations & Transitions

**Slide Change:**
```css
/* Fade Transition */
opacity: 0 → 1 (300ms, ease-in-out)

/* Optional: Zoom + Fade */
opacity: 0, scale: 1.1 → opacity: 1, scale: 1 (400ms, ease-out)
```

**Content Elements Animation:**
1. Tagline: Fade in (200ms)
2. Headline: Slide up + fade (300ms, 200ms delay)
3. Subtitle: Fade in (400ms, 400ms delay)
4. CTA Button: Fade in + slight scale (300ms, 600ms delay)

---

## 4. CAMPAIGN DATES & LOCATIONS

### 4.1 Campaign Timeline

**Phase 1: Announcement & Foundation (January - February 2026)**
- Official candidacy announcement
- Campaign office opening events
- Initial town halls in major LGAs
- Website & social media launch
- Campaign materials distribution begins

**Phase 2: Grassroots Engagement (March - June 2026)**
- Monthly town halls across 20 LGAs
- Community listening sessions
- Volunteer recruitment drives
- Policy dialogue forums
- Young professionals engagement events

**Phase 3: Intensive Campaign (July - December 2026)**
- Major campaign rallies (10-15 key locations)
- Media campaigns intensified
- Debates and public forums
- Stakeholder engagement (business, education, health)
- Merchandise and materials distribution

**Phase 4: Final Push (January - March 11, 2027)**
- Final rallies and events
- Get Out The Vote (GOTV) campaigns
- Voter registration closes (March 4, 2027)
- Election day: Saturday, March 11, 2027
- Post-election transition planning

### 4.2 Campaign Locations & Schedule Section

**Website Feature: Interactive Campaign Calendar**
- Map view showing upcoming events
- List view with dates, times, locations
- Filter by LGA, event type, date range
- RSVP functionality
- Calendar export option
- Real-time updates

**Key Campaign Locations (Sample):**
```
MAINLAND REGION:
- Ikoyi Community Hall - March 20, 2026 2:00 PM
- Lekki Phase 1 Park - April 10, 2026 3:00 PM
- Lagos Island Civic Center - April 25, 2026 2:30 PM
- VI Town Hall - May 8, 2026 4:00 PM

ISLAND REGION:
- Ajah Community Center - March 22, 2026 2:00 PM
- Epe Town Hall - April 15, 2026 3:30 PM
- Badagry Heritage Museum - May 10, 2026 2:00 PM

IKORODU REGION:
- Ikorodu Central Hall - March 25, 2026 2:00 PM
- Sagamu Road Community Center - April 28, 2026 3:00 PM

AJEROMI-IFELODUN AREA:
- Apapa Sports Complex - March 27, 2026 2:00 PM
- Bariga Community Hall - May 15, 2026 3:00 PM

ALIMOSHO REGION:
- Ifako-Ijaiye LG Hall - March 29, 2026 2:00 PM
- Alimosho Central Venue - May 20, 2026 3:00 PM

KOSOFE/IBEJU-LEKKI REGION:
- Kosofe Town Hall - April 5, 2026 2:30 PM
- Ibeju-Lekki Community Center - May 25, 2026 2:00 PM
```

### 4.3 On-Page Campaign Timeline Component

**Design:**
- Vertical timeline on desktop (3-column layout on tablet/mobile)
- Color-coded phases (each phase different color)
- Icons for each phase type
- Expandable event details
- Hover to reveal full information

---

## 5. SOCIAL MEDIA INTEGRATION

### 5.1 Integrated Social Platforms

**Primary Platforms:**

1. **Facebook** (@KadriObafemiHamzat)
   - Community building and engagement
   - Event promotion and RSVPs
   - Live streams from rallies
   - User-generated content sharing
   - Voter registration information
   - Age reach: 35+ primarily, all demographics

2. **Twitter/X** (@KOHforLagos)
   - Real-time campaign updates
   - Policy announcements
   - Engagement with current events
   - News and media coverage
   - Trending topics participation
   - Age reach: 25-45, professionals

3. **Instagram** (@kohforlacosstate)
   - Visual storytelling (Stories, Reels)
   - Behind-the-scenes content
   - Campaign highlights
   - Quotes and inspiration
   - Event coverage
   - Age reach: 18-40, youth-focused

4. **TikTok** (@KadriForLagos)
   - Viral campaign messages
   - Youth engagement
   - Trending sounds/challenges
   - Quick policy explainers
   - Entertainment value
   - Age reach: 13-35, Gen Z focus

5. **YouTube** (Kadri Obafemi Hamzat Official)
   - Long-form videos and speeches
   - Campaign documentary
   - Policy deep-dives
   - Past achievements showcase
   - Debate recordings
   - Town hall full videos
   - Age reach: All demographics

6. **LinkedIn** (Kadri Obafemi Hamzat)
   - Professional network engagement
   - Economic policy discussions
   - Business community outreach
   - Thought leadership
   - Professional achievements
   - Age reach: 30-60, professionals

### 5.2 Website Social Integration

**Social Media Feed Section:**
- Homepage features live feed from all platforms
- Auto-rotating social posts (Instagram/Twitter/TikTok)
- Click-through to full platforms
- Follow buttons for each platform
- Responsive gallery display
- Update frequency: Real-time

**Social Media Sidebar (Secondary Page):**
- Follow buttons for all platforms (floating)
- Social media stats (follower counts, engagement)
- Latest posts from each platform
- Share buttons on content

**Social Sharing Functionality:**
- Article/content social share buttons (Twitter, Facebook, LinkedIn, WhatsApp)
- Custom share text with candidate name
- Email sharing option
- QR code generation for events/pages

### 5.3 Social Media Content Strategy

**Post Frequency:**
- Facebook: 2-3 posts daily (mix of original, shared content)
- Twitter: 4-6 tweets daily (real-time, engagement)
- Instagram: 2-3 posts daily + daily Stories
- TikTok: 3-5 videos weekly (trending, educational)
- YouTube: 2-4 videos weekly (long-form)
- LinkedIn: 3-4 posts weekly (professional content)

**Content Pillars:**
- Vision & Policy (30%)
- Community Engagement & Events (25%)
- Achievements & Track Record (20%)
- Call-to-Action & Voting Info (15%)
- Trending Topics & Social (10%)

**Cross-Promotion Strategy:**
- YouTube videos embedded on website and promoted across platforms
- TikTok clips created from longer YouTube content
- Instagram Reels from TikTok videos
- Twitter threads from blog articles
- Facebook album collections from Instagram Stories
- LinkedIn thought pieces from speeches

---

## 6. CAMPAIGN MATERIALS & RESOURCES

### 6.1 Downloadable Campaign Materials Section

**Website Feature: Materials Hub**
- Organized by material type and category
- Search and filter functionality
- Download statistics tracking
- Material update dates
- Print-ready formats where applicable

### 6.2 Brochures & Policy Documents

**Main Campaign Brochure**
- Format: PDF (A4 tri-fold or single page)
- Content: Candidate biography, vision, 3 key pillars
- Dimensions: 210mm x 297mm (A4)
- File size: <5MB (optimized for download)
- Languages: English (primary), Yoruba (secondary)
- Design: Use primary color scheme, professional layout

**Issue-Specific Brochures** (4-6 documents)
1. **Economic Development & Job Creation**
   - Plan for SME support
   - Job creation targets
   - Investment strategy
   
2. **Education & Human Capital Development**
   - Educational infrastructure plans
   - Scholarship programs
   - Skills training initiatives
   
3. **Healthcare & Well-being**
   - Healthcare expansion plans
   - Health services accessibility
   - Mental health initiatives
   
4. **Infrastructure & Transportation**
   - Road rehabilitation plans
   - Public transport improvements
   - Water and sanitation projects
   
5. **Security & Public Safety**
   - Community policing initiatives
   - Crime prevention strategies
   - Emergency response plans
   
6. **Entertainment & Culture**
   - Creative industry support
   - Tourism development
   - Cultural preservation

**Campaign Manifesto**
- Comprehensive document (30-50 pages)
- Detailed policy positions
- Implementation timelines
- Success metrics and accountability
- Professional design with data visualization

**Fact Sheet Documents**
- 1-page overview of key achievements
- 1-page summary of vision
- 1-page voting & registration information
- 1-page candidate biography

### 6.3 Social Media Graphics & Templates

**Pre-designed Templates** (Canva Pro or Adobe Express)

**Quote Graphics:**
- Size: 1080x1350px (Instagram), 1200x630px (Facebook), 1024x512px (Twitter)
- Template: Quote + candidate image + color gradient
- Editable text fields
- 5-10 pre-made variations

**Event Announcement Graphics:**
- Template: Date, time, location, RSVP link
- Sizes: Multiple formats (Instagram Story 1080x1920, Feed 1080x1350)
- QR code integration area
- Color variations for different events

**Policy Announcement Graphics:**
- Title + 3-4 key points
- Icon illustrations
- Color-coded by policy area
- Shareable quote versions

**Infographics:**
- Achievement statistics (2-3 templates)
- Policy comparison charts
- Candidate experience timeline
- Vision roadmap graphics

**Reels/Video Templates:**
- 15-second policy explainer template
- Testimonial video template (30-60 seconds)
- Event coverage quick-cut template
- Q&A format template

**Story Templates** (Instagram/Facebook Stories):
- Poll stickers for voter engagement
- Countdown stickers for events
- Question stickers for feedback
- Event reminder templates

**Badges & Stickers:**
- Campaign logo variations
- "Vote for Kadri" stickers
- Event attendance badges
- Supporter/Volunteer badges

### 6.4 Email Newsletter Templates

**Email Campaign Format:**

**Standard Newsletter Template**
- Header: Campaign logo + main image (600x300px)
- Section 1: Featured announcement/hero content
- Section 2: 2-3 article previews with links
- Section 3: Upcoming events (with dates, locations, links)
- Section 4: Call-to-action buttons (Volunteer, Donate, Share)
- Section 5: Social media links
- Footer: Contact info, address, legal disclaimers
- Mobile-responsive (tested across clients)
- Unsubscribe link (legal requirement)

**Event Announcement Email**
- Large hero image of event location/type
- Event title, date, time, location
- Detailed description and agenda
- RSVP button with link
- Directions/parking information
- Contact person information
- Social sharing links

**Policy Update Email**
- Announcement of new policy release
- Summary of key points
- Download link to full document
- Related articles/resources
- Call-to-action (Share, Discuss, etc.)

**Volunteer Onboarding Email Series** (5 emails)
1. Welcome + appreciation
2. Volunteer opportunities overview
3. Training resources and schedule
4. Role-specific instructions
5. Community and support groups

**Supporter Newsletter Email**
- Monthly update on campaign progress
- Achievements and milestones
- Upcoming events and opportunities
- Impact stories and testimonials
- Ways to get more involved

### 6.5 Event Materials

**Campaign Rally Event Kit**

**Invitation Design:**
- Size: 4x6 inches (print) or digital (Instagram/Facebook)
- Content: Event details, RSVP method, venue map
- Design: Branded with event-specific graphics
- Digital version: QR code linking to event page

**Event Program/Agenda:**
- Printed program for attendees
- Schedule of speakers and activities
- Candidate biography for new attendees
- Campaign platform summary
- Volunteer contact information

**Banners & Signage:**
- Main stage backdrop: 10ft x 8ft vinyl banner
  - Campaign logo, candidate image, tagline
  - Contact information and social handles
  - Official colors and branding

- Registration/Check-in banners: 4ft x 3ft
- Directional signage (wayfinding)
- Event date/time/location signs

**Press Kit/Media Pack:**
- Candidate high-resolution photos (min 300 DPI)
  - Professional headshot (8x10, 6x4, web sizes)
  - Campaign photos (action shots, community engagement)
- Candidate biography (short & long versions)
- Campaign fact sheet
- Media contact information
- Downloadable as ZIP file

**Stage Setup Materials:**
- Podium signage with campaign branding
- Speaker backdrop
- Table covers with logo
- Chair covers or sashes

**Event Merchandise/Swag:**
- Printed programs
- Note pads with campaign branding
- Stickers and badges
- Flyer handouts

### 6.6 Campaign Merchandise

**Website Merchandise Shop Section**

**Featured Products:**

1. **T-Shirts**
   - Style: Premium cotton, crew neck
   - Colors: Primary blue, white, dark gray
   - Designs: Candidate image + slogan, logo centered, full back print
   - Sizes: XS - 3XL
   - Price: Suggested retail
   - Printing: Screen print or direct-to-garment

2. **Face Caps/Baseball Caps**
   - Style: Adjustable or fitted
   - Colors: Blue, white, gold
   - Design: Embroidered logo, candidate name, or campaign slogan
   - Printing method: Embroidery (front/back)
   - Material: Cotton twill or polyester

3. **Hoodies**
   - Style: Pullover or zip-up
   - Colors: Blue, gray, black
   - Design: Campaign logo, candidate image variations
   - Material: 80% cotton, 20% polyester
   - Sizes: XS - 2XL
   - Special: Heat-resistant print for durability

4. **Polos**
   - Style: Collared, classic fit
   - Colors: Blue with white trim, white with blue trim
   - Embroidery: Campaign logo on chest
   - Material: Performance blend (moisture-wicking)
   - Professional appearance for supporters/volunteers

5. **Tote Bags**
   - Style: Canvas, durable
   - Colors: Natural canvas with blue print
   - Design: Campaign slogan + logo
   - Capacity: Large (fit A4 documents)
   - Use: Event giveaway, merchandise store item

6. **Wristbands**
   - Material: Silicone
   - Colors: Blue, gold, white
   - Text: Campaign slogan or "Vote 2027"
   - Size: One-size-fits-all
   - Use: Event identification, supporter giveaway

7. **Stickers**
   - Material: Vinyl, weather-resistant
   - Designs: Logo, campaign slogan, "I Voted" designs
   - Sizes: Small (2"), medium (4"), large (6")
   - Use: Car bumpers, water bottles, laptops

8. **Buttons/Badges**
   - Size: 1" and 1.5" options
   - Designs: Logo, motivational messages, event-specific
   - Material: Metal with safety pin backing
   - Use: Event giveaway, supporter identification

9. **Umbrellas**
   - Style: Golf umbrella (large coverage)
   - Colors: Blue with white/gold accents
   - Print: Campaign logo and slogan
   - Use: Premium giveaway, functional utility

10. **Phone Cases**
    - Compatibility: iPhone, Samsung (popular models)
    - Design: Campaign logo, candidate image options
    - Material: Durable plastic or silicone
    - Print: High-quality digital print

**Merchandise Shop Features:**
- Product gallery with zoom and colors/sizes
- Add to cart functionality
- Multiple payment options
- Shipping calculator
- Order tracking
- Returns policy display
- Customer reviews/ratings

**Bulk Order Section:**
- For organizations, supporters, teams
- Volume discounts displayed
- Custom design consultation option
- Corporate packages available

---

## 7. CORE PAGES & SECTIONS

### 7.1 Homepage Layout

```
1. NAVIGATION BAR (sticky)
   - Logo (left)
   - Menu items (center): Home, About, Platforms, Gallery, Events, News, Shop, Contact
   - CTA buttons (right): Volunteer, Donate
   - Mobile hamburger menu

2. HERO SLIDER (with 5 slides as detailed in Section 3)
   - Full viewport height
   - Auto-rotating slides
   - Social media indicators
   
3. ABOUT THE CANDIDATE
   - 2-column layout (text + image)
   - Quick biography
   - Key achievements
   - Vision statement
   - Learn more link
   
4. CAMPAIGN PLATFORMS (3 Pillars)
   - 3-column grid
   - Icons, titles, descriptions
   - Color-coded sections
   - Expandable detailed content
   
5. CAMPAIGN TIMELINE/EVENTS
   - Interactive calendar
   - Upcoming events list
   - Map view of locations
   - RSVP buttons
   
6. KEY ACHIEVEMENTS
   - 4-column grid card layout
   - Icons + numbers + descriptions
   - Hover: Lift effect with shadow
   - Statistics and impact metrics
   
7. SOCIAL MEDIA FEED
   - Live feed carousel
   - Posts from all platforms
   - Follow buttons
   - Real-time updates
   
8. TESTIMONIALS CAROUSEL
   - 3-column grid (desktop)
   - Auto-rotate testimonials
   - Quote marks, names, titles
   - Profile images
   
9. CAMPAIGN GALLERY
   - Masonry grid of campaign photos
   - 4 columns (desktop), 2 (tablet), 1 (mobile)
   - Lightbox on click
   - Filter by event/category
   
10. CALL-TO-ACTION SECTION
    - Large headline: "Join the Movement"
    - Newsletter signup form
    - Volunteer registration button
    - Donation call-to-action
    - Background: Primary blue
    - Text: White
    
11. MATERIALS & RESOURCES
    - Quick links to downloads
    - Brochures, policies, graphics
    - Campaign merchandise showcase
    - Social media templates
    
12. FOOTER
    - Multi-column layout
    - Logo, navigation, social links
    - Contact information
    - Campaign office address & phone
    - Social media links (all platforms)
    - Newsletter signup
    - Copyright & privacy policy
    - Legal disclaimers
```

### 7.2 Key Secondary Pages

**About Page:**
- Detailed candidate biography
- Professional background
- Education and qualifications
- Community involvement history
- Family background (if appropriate)
- Personal interests/hobbies
- High-resolution photos
- Download full biography PDF

**Platforms/Policies Page:**
- 5-6 policy areas detailed
- Each with: Overview, key initiatives, timeline, expected outcomes
- downloadable detailed documents
- Visual infographics
- Related news articles
- FAQ section

**Events & Campaign Schedule Page:**
- Interactive calendar (monthly view)
- List view with filters (by LGA, date, type)
- Map view of all locations
- Event details: time, location, agenda, speakers
- RSVP functionality
- Share buttons
- Add to personal calendar option

**Gallery Page:**
- Masonry grid layout
- Filter options (by year, category, event type)
- Lightbox viewer for images
- Image titles and descriptions
- Social sharing buttons
- High-resolution download option

**News & Updates Page:**
- Blog-style article listing
- Categories and tags
- Search functionality
- Featured articles
- Related articles recommendations
- Social sharing
- Comment section (moderated)

**Shop Page:**
- Product grid with categories
- Filter by type, color, size
- Product details and images
- Price display
- Add to cart
- Continue shopping
- Checkout process

**Contact Page:**
- Contact form with fields (name, email, subject, message)
- Campaign office address and map
- Phone numbers and email
- Office hours
- Social media links
- Mailing list subscription

**Privacy & Legal Page:**
- Privacy policy
- Terms of service
- Cookie policy
- Legal disclaimers
- Campaign finance disclosure (if required)

---

## 8. NAVIGATION BAR DESIGN

### 8.1 Desktop Navigation

- **Logo**: 40x40px on left
- **Center Menu**: Home | About | Platforms | Gallery | Events | News | Shop | Contact
- **Right Actions**: 
  - "Volunteer" button (Primary Blue outline)
  - "Donate" button (Primary Blue filled)
- **Sticky**: On scroll, reduced padding, subtle shadow
- **Font**: 16px, semi-bold
- **Active state**: Blue underline or text color
- **Hover**: Color change + smooth transition

### 8.2 Mobile Navigation

- **Logo**: 36x36px left
- **Hamburger menu**: Right side
- **Menu items**: Full-width stacked list
- **Slide-out panel**: From right side
- **Actions**: Full-width buttons under menu
- **Search**: Optional search bar
- **Social icons**: In menu footer

---

## 9. COMPONENT SPECIFICATIONS

### 9.1 Buttons

**Primary Button**
- Background: `#003D82` (Primary Blue)
- Text: White, 16px, weight 600
- Padding: 12px 32px
- Border-radius: 4px
- Cursor: pointer
- Transition: All 200ms ease
- Hover: Background `#002255`, transform scale(1.02)
- Active: Background `#001840`, box-shadow inset
- Disabled: Opacity 0.5, cursor not-allowed

**Secondary Button (Outlined)**
- Background: transparent
- Border: 2px solid Primary Blue
- Text: Primary Blue, 16px, weight 600
- Padding: 10px 30px
- Hover: Background `#E8F0F8`
- Active: Background light blue

**Gold/Accent Button**
- Background: `#FFB81C`
- Text: Dark Gray, 16px, weight 600
- Padding: 12px 32px
- Hover: Background `#E5A500`, scale 1.02
- Active: Background `#CC9300`

**Button Sizes:**
- Small: 10px 20px, 14px font
- Medium: 12px 32px, 16px font
- Large: 14px 40px, 18px font

### 9.2 Cards

**Standard Card**
- Background: White
- Border: 1px solid `#E8E8E8`
- Border-radius: 8px
- Padding: 24px
- Box-shadow: Shadow 1
- Transition: All 200ms ease
- Hover: Shadow 2, transform translateY(-4px)

**Achievement Card**
- Icon container: 56x56px, light blue background
- Icon: 32x32px, primary blue color
- Title: H4 (20px)
- Stat number: 32px, accent gold
- Description: Body small
- Hover: Icon background shifts to gold, card lifts

**Testimonial Card**
- Quote mark: Large (48px), accent gold, opacity 0.2
- Quote text: Italic, 18px, dark gray
- Author name: Semi-bold, 16px
- Author title: 14px, light gray
- Avatar: 48x48px circle image
- Border-left: 4px accent gold

### 9.3 Forms

**Input Fields:**
- Background: White or light gray
- Border: 1px solid `#D0D0D0`
- Border-radius: 4px
- Padding: 12px 16px
- Font-size: 16px
- Focus: Border color primary blue, box-shadow subtle
- Error: Border color red, helper text red

**Labels:**
- Font-size: 14px, weight 600
- Color: Dark gray
- Margin-bottom: 8px
- Required indicator: Red asterisk (*)

**Email Subscription Form:**
- Two-column layout (input + button) on desktop
- Input: Flexible width, placeholder text
- Button: Fixed width, primary blue
- Mobile: Stacked vertically
- Success message: Green checkmark + confirmation text
- Error message: Red text with guidance

**Contact Form:**
- Fields: Name, Email, Subject, Message
- Required field indicators
- Validation: Client-side + server-side
- Submission: AJAX for smooth experience
- Success: Modal or inline confirmation message
- CAPTCHA: reCAPTCHA v3 for spam prevention

---

## 10. RESPONSIVE DESIGN

### 10.1 Breakpoints

- **Mobile**: 0px - 639px
- **Tablet**: 640px - 1023px
- **Desktop**: 1024px+
- **Large Desktop**: 1440px+

### 10.2 Hero Slider Responsiveness

**Desktop (1024px+):**
- Height: 100vh
- Headline: 56px
- Subtitle: 18px
- Content width: 50% of viewport
- Arrows visible, centered
- Dots visible at bottom

**Tablet (640px - 1023px):**
- Height: 70vh (min 450px)
- Headline: 40px
- Subtitle: 16px
- Content width: 60% of viewport
- Padding: 32px
- Arrows smaller (36px)
- Dots: 6px size

**Mobile (0px - 639px):**
- Height: 60vh (min 350px)
- Headline: 28px
- Subtitle: 14px
- Content width: 100%, centered
- Padding: 20px
- Arrows: Hidden (swipe enabled)
- Dots: 6px, bottom spacing 16px

### 10.3 Grid Responsiveness

**Desktop:**
- 4-column grid for cards
- 3-column grid for features
- 2-column sidebar layouts

**Tablet:**
- 2-column grid for cards
- 2-column grid for features
- Full-width content

**Mobile:**
- 1-column (stacked) for all grids
- Full-width content
- Full-width buttons

### 10.4 Navigation Responsiveness

**Desktop:**
- Horizontal menu visible
- Logo + menu + action buttons
- Height: 64px

**Mobile:**
- Hamburger menu visible
- Menu collapses into slide-out panel
- Height: 56px
- Full-width buttons in menu

---

## 11. ACCESSIBILITY

### 11.1 Standards
- WCAG 2.1 AA compliance
- Semantic HTML (nav, main, section, article)
- ARIA labels for interactive elements
- Keyboard navigation support (Tab order)

### 11.2 Color Contrast
- Normal text: 4.5:1 contrast ratio minimum
- Large text (18px+): 3:1 contrast ratio minimum
- Focus indicators: 3:1 contrast ratio

### 11.3 Interactive Elements
- Focus visible with 2px outline (primary blue)
- Logical keyboard tab order
- Click targets: Minimum 44x44px
- Skip-to-content link in header
- Hover states indicate interactivity

### 11.4 Images
- Alt text for all images (descriptive)
- Slider images: Campaign context alt text
- Icons: ARIA labels or alt text
- Decorative images: Empty alt="" attribute

### 11.5 Forms
- Labels associated with inputs (for attribute)
- Error messages linked to inputs (aria-describedby)
- Required fields marked clearly
- Validation messages clear and helpful

---

## 12. TECHNICAL IMPLEMENTATION

### 12.1 Technology Stack

**Frontend:**
- Vue.js 3 (component framework)
- Tailwind CSS (utility-first styling)
- Swiper.js (hero slider library)
- Axios (HTTP client)
- Vite (build tool - already configured)

**Backend:**
- Laravel 11 (framework)
- Inertia.js (SPA with server-side rendering)
- Laravel Sanctum (API authentication)
- Eloquent ORM (database)

**Database:**
- SQLite (development) / MySQL (production)
- Migration system for schema management

**Assets:**
- Sharp (image optimization)
- Laravel Mix or Vite for asset compilation

**Email:**
- Laravel Mail (SMTP configuration)
- Template system for newsletters

**Storage:**
- Laravel Storage (local/cloud files)
- S3 or local storage for merchandise images

### 12.2 Hero Slider Implementation

**Library: Swiper.js v11+**

```javascript
// Recommended Swiper configuration
import Swiper from 'swiper/bundle'
import 'swiper/css/bundle'

const heroSlider = new Swiper('.hero-slider', {
  loop: true,
  autoplay: {
    delay: 6000,
    disableOnInteraction: false,
    pauseOnMouseEnter: true
  },
  pagination: {
    el: '.swiper-pagination',
    clickable: true,
    type: 'bullets',
    bulletActiveClass: 'swiper-pagination-bullet-active'
  },
  navigation: {
    nextEl: '.swiper-button-next',
    prevEl: '.swiper-button-prev'
  },
  effect: 'fade',
  fadeEffect: {
    crossFade: true
  },
  speed: 300,
  touchRatio: 1,
  resistanceRatio: 0.85,
  a11y: {
    enabled: true,
    prevSlideMessage: 'Previous slide',
    nextSlideMessage: 'Next slide',
    paginationBulletMessage: 'Go to slide {{index}}'
  },
  breakpoints: {
    0: { height: '60vh' },
    640: { height: '70vh' },
    1024: { height: '100vh' }
  }
})
```

### 12.3 Directory Structure

```
resources/
├── js/
│   ├── Components/
│   │   ├── HeroSlider.vue
│   │   ├── Navigation.vue
│   │   ├── Card.vue
│   │   ├── Button.vue
│   │   ├── FormInput.vue
│   │   ├── SocialFeed.vue
│   │   ├── EventCalendar.vue
│   │   ├── MaterialsHub.vue
│   │   ├── Footer.vue
│   │   └── MerchandiseShop.vue
│   ├── Pages/
│   │   ├── Home.vue
│   │   ├── About.vue
│   │   ├── Platforms.vue
│   │   ├── Gallery.vue
│   │   ├── Events.vue
│   │   ├── News.vue
│   │   ├── Shop.vue
│   │   ├── Contact.vue
│   │   └── Materials.vue
│   ├── Layouts/
│   │   └── AppLayout.vue
│   ├── Composables/
│   │   ├── useSocialMedia.js
│   │   ├── useEvents.js
│   │   └── useMerchandise.js
│   └── app.js
├── css/
│   ├── app.css (Tailwind imports)
│   └── components/
│       ├── slider.css
│       ├── buttons.css
│       ├── forms.css
│       └── animations.css
└── views/
    └── app.blade.php

app/
├── Http/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   ├── EventController.php
│   │   ├── NewsController.php
│   │   ├── MaterialController.php
│   │   ├── MerchandiseController.php
│   │   └── SocialMediaController.php
│   └── Requests/
│       ├── ContactFormRequest.php
│       ├── NewsletterRequest.php
│       └── VolunteerRequest.php
├── Models/
│   ├── Event.php
│   ├── NewsArticle.php
│   ├── Material.php
│   ├── Merchandise.php
│   ├── Testimonial.php
│   └── Contact.php
└── Mail/
    ├── NewsletterMail.php
    ├── ContactConfirmationMail.php
    └── VolunteerWelcomeMail.php

database/
├── migrations/
│   ├── create_events_table.php
│   ├── create_news_articles_table.php
│   ├── create_materials_table.php
│   ├── create_merchandise_table.php
│   ├── create_testimonials_table.php
│   ├── create_newsletter_subscribers_table.php
│   └── create_contacts_table.php
└── seeders/
    ├── EventSeeder.php
    └── MerchandiseSeeder.php

storage/
├── app/
│   ├── campaigns/
│   ├── materials/
│   ├── gallery/
│   └── merchandise/
└── logs/
```

### 12.4 API Endpoints (Inertia/RESTful)

```
GET  /                          - Homepage
GET  /about                      - About page
GET  /platforms                  - Platforms/Policies page
GET  /gallery                    - Gallery page
GET  /events                     - Events page
GET  /events/{id}               - Event detail

GET  /news                       - News listing
GET  /news/{slug}               - Article detail

GET  /materials                  - Materials hub
GET  /materials/{id}/download    - Download material

GET  /shop                       - Merchandise listing
POST /shop/{id}/cart            - Add to cart
GET  /shop/checkout             - Checkout page
POST /shop/order                - Place order

POST /contact                    - Contact form submission
POST /newsletter/subscribe       - Newsletter signup
POST /volunteer/register        - Volunteer registration

GET  /api/social-feed           - Social media feed (JSON)
GET  /api/events                - Events API (JSON)
GET  /api/merchandise           - Merchandise API (JSON)
```

### 12.5 Key CSS Classes (Tailwind + Custom)

```css
/* Hero Slider */
.hero-slider { /* Swiper container */ }
.hero-slide { /* Individual slide */ }
.hero-overlay { /* Dark gradient overlay */ }
.hero-content { /* Text overlay container */ }
.hero-tagline { /* Accent gold, uppercase */ }
.hero-headline { /* H1, white, animated */ }
.hero-subtitle { /* Body large, light gray */ }
.hero-button { /* CTA button styling */ }

/* Navigation */
.navbar { /* Sticky header */ }
.navbar-sticky { /* Reduced padding on scroll */ }
.nav-logo { /* Logo styling */ }
.nav-menu { /* Menu items */ }
.nav-item { /* Individual menu item */ }
.nav-item.active { /* Active menu state */ }
.nav-button { /* CTA buttons */ }
.nav-mobile { /* Mobile menu */ }
.hamburger-menu { /* Hamburger icon */ }

/* Cards & Containers */
.card { /* Standard card */ }
.card-hover { /* Hover lift effect */ }
.achievement-card { /* Achievement card specific */ }
.testimonial-card { /* Testimonial card */ }

/* Buttons */
.btn-primary { /* Primary button */ }
.btn-secondary { /* Secondary/outlined button */ }
.btn-gold { /* Gold accent button */ }
.btn-small { /* Small button size */ }
.btn-medium { /* Medium button size */ }
.btn-large { /* Large button size */ }

/* Forms */
.form-input { /* Input field */ }
.form-label { /* Form label */ }
.form-error { /* Error state */ }
.form-required { /* Required indicator */ }

/* Layout */
.container-max { /* Max-width 1200px */ }
.section-spacing { /* Padding and margins */ }
.grid-2 { /* 2-column grid */ }
.grid-3 { /* 3-column grid */ }
.grid-4 { /* 4-column grid */ }

/* Animations */
.fade-in { /* Fade animation */ }
.slide-up { /* Slide up animation */ }
.scale-hover { /* Scale on hover */ }
.text-gradient { /* Gradient text effect */ }

/* Social Media */
.social-feed { /* Social feed container */ }
.social-post { /* Individual social post */ }
.social-icon { /* Social platform icon */ }

/* Events */
.event-card { /* Event card styling */ }
.event-calendar { /* Calendar component */ }
.event-map { /* Map view */ }

/* Materials */
.materials-grid { /* Materials grid */ }
.material-item { /* Individual material */ }
.download-btn { /* Download button */ }

/* Merchandise */
.product-grid { /* Product grid */ }
.product-card { /* Product card */ }
.product-image { /* Product image */ }
.add-to-cart { /* Add to cart button */ }

/* General */
.text-center { /* Center text */ }
.mt-md { /* Margin top medium */ }
.mb-lg { /* Margin bottom large */ }
.shadow-md { /* Medium shadow */ }
.border-radius-md { /* Medium border radius */ }
```

---

## 13. PERFORMANCE OPTIMIZATION

### 13.1 Image Optimization
- WebP format with PNG/JPG fallback
- Lazy loading for below-fold images
- Responsive images (srcset)
- Image compression and optimization
- CDN delivery for static assets

### 13.2 Code Splitting
- Separate bundles for different pages
- Lazy load non-critical components
- Tree-shaking unused code

### 13.3 Caching Strategy
- Browser caching for static assets
- Service worker for offline capability
- Server-side caching for API responses

### 13.4 Performance Targets
- First Contentful Paint (FCP): < 2s
- Largest Contentful Paint (LCP): < 2.5s
- Cumulative Layout Shift (CLS): < 0.1
- Time to Interactive (TTI): < 3.5s
- Lighthouse score: 90+

---

## 14. SECURITY CONSIDERATIONS

### 14.1 Form Security
- CSRF protection on all forms
- Rate limiting on submissions
- Server-side validation for all inputs
- Sanitization of user-generated content
- CAPTCHA on contact form

### 14.2 Data Protection
- HTTPS/SSL encryption
- Secure password hashing
- Environment variables for sensitive data
- Regular security audits

### 14.3 Payment Security (if applicable)
- PCI DSS compliance
- Secure payment gateway integration
- Tokenized payments (no direct card storage)
- SSL certificate and security headers

---

## 15. SEO OPTIMIZATION

### 15.1 On-Page SEO
- Semantic HTML structure
- Meta descriptions for all pages
- Structured data (Schema.org markup)
- Open Graph tags for social sharing
- Mobile-friendly design
- Fast page load speed

### 15.2 Content Strategy
- Keyword research and optimization
- Regular blog updates
- Internal linking strategy
- Long-form content for key topics

### 15.3 Technical SEO
- XML sitemap
- Robots.txt configuration
- Canonical tags
- Proper HTTP status codes
- Mobile optimization

---

## 16. ANALYTICS & TRACKING

### 16.1 Tracking Implementation
- Google Analytics 4 integration
- Event tracking for key actions (signups, downloads, shares)
- Social media conversion tracking
- Goal tracking (donations, volunteer signups)
- Custom dashboard for campaign metrics

### 16.2 Key Metrics
- Website traffic and sessions
- Page views and bounce rates
- Conversion rates (donations, signups)
- Social media engagement metrics
- Email newsletter metrics
- Download statistics for materials

---

## 17. BROWSER SUPPORT

- Chrome/Edge: Latest 2 versions
- Firefox: Latest 2 versions
- Safari: Latest 2 versions (iOS 12+, macOS)
- Mobile browsers: iOS Safari 12+, Chrome Android latest

---

## 18. CONTENT GUIDELINES

### 18.1 Tone & Voice
- Professional yet approachable
- Optimistic and forward-looking
- Inclusive and community-focused
- Clear and direct messaging
- Evidence-based claims

### 18.2 Image Guidelines
- Aspect ratios: 16:9 (hero), 4:3 (cards), 1:1 (avatars), 2:3 (merchandise)
- File format: WebP with JPG fallback
- Hero images: 1920px wide minimum (2x: 3840px)
- High quality, professional photography
- Diverse representation in images
- All images must have descriptive alt text

### 18.3 Copy Length Guidelines
- Hero tagline: 2-4 words, UPPERCASE
- Hero headline: 5-10 words, impactful
- Hero subtitle: 1 sentence (12-15 words)
- Card title: 2-5 words
- Card description: 1-2 sentences
- Body paragraphs: 3-5 sentences max
- Policy descriptions: 150-250 words

---

## 19. LEGAL & COMPLIANCE

### 19.1 Required Pages
- Privacy Policy
- Terms of Service
- Cookie Policy
- Accessibility Statement
- Campaign Finance Disclosure (if applicable)

### 19.2 Regulations
- GDPR compliance for email marketing
- Email marketing CAN-SPAM compliance
- Accessibility (WCAG 2.1 AA)
- Data protection laws (Nigeria-specific)

---

## 20. IMPLEMENTATION TIMELINE

### Phase 1: Setup & Core (Weeks 1-3)
- [ ] Design system finalization
- [ ] Database schema and migrations
- [ ] Navigation and layout components
- [ ] Hero slider component
- [ ] Basic pages (Home, About, Contact)

### Phase 2: Features (Weeks 4-6)
- [ ] Events and calendar system
- [ ] News/Blog functionality
- [ ] Materials hub with downloads
- [ ] Merchandise shop
- [ ] Social media feed integration

### Phase 3: Polish & Launch (Weeks 7-8)
- [ ] Content population
- [ ] SEO optimization
- [ ] Performance optimization
- [ ] Security hardening
- [ ] Testing (cross-browser, mobile, accessibility)
- [ ] Launch preparation
- [ ] Analytics setup

### Phase 4: Post-Launch (Ongoing)
- [ ] Monitor performance
- [ ] Content updates
- [ ] Social media strategy execution
- [ ] Campaign analytics tracking
- [ ] Regular maintenance and improvements

---

## 21. MAINTENANCE & UPDATES

### 21.1 Regular Tasks
- Update campaign events and schedule
- Publish news and blog articles
- Monitor and respond to contacts
- Update social media feeds
- Check analytics and performance metrics

### 21.2 Seasonal Updates
- Event promotion materials
- Holiday/special occasion campaigns
- Policy updates and announcements
- Email newsletter campaigns

### 21.3 Monitoring
- Website uptime monitoring
- Performance monitoring (Lighthouse)
- SEO ranking tracking
- Social media engagement metrics
- Email campaign metrics

---

## 22. NEXT STEPS & DELIVERABLES

### Immediate Tasks
1. **Asset Preparation**
   - [ ] Gather high-quality campaign images (hero slider, gallery, about page)
   - [ ] Create candidate portrait variations (headshot, full-body, action shots)
   - [ ] Prepare event photography from past campaigns
   - [ ] Design campaign logo variations and exports

2. **Content Development**
   - [ ] Write candidate biography (short and long versions)
   - [ ] Develop campaign manifesto and policy documents
   - [ ] Create FAQ content
   - [ ] Write testimonials and success stories
   - [ ] Draft email newsletter templates

3. **Design Refinement**
   - [ ] Create Figma/Adobe XD mockups based on this specification
   - [ ] Finalize color palette in context
   - [ ] Design component library (buttons, cards, forms)
   - [ ] Create responsive design mockups
   - [ ] Get stakeholder approval on designs

4. **Development Setup**
   - [ ] Install and configure Swiper.js
   - [ ] Set up Tailwind CSS configuration
   - [ ] Create component structure
   - [ ] Set up database migrations
   - [ ] Configure authentication system
   - [ ] Set up email configuration

5. **Feature Implementation**
   - [ ] Build Navigation component
   - [ ] Develop HeroSlider component with animations
   - [ ] Create reusable Card components
   - [ ] Build Forms (Contact, Newsletter, Volunteer)
   - [ ] Develop Events system
   - [ ] Create Materials hub with downloads
   - [ ] Build Merchandise shop
   - [ ] Integrate social media feeds

6. **Testing & QA**
   - [ ] Cross-browser testing (Chrome, Firefox, Safari, Edge)
   - [ ] Mobile responsiveness testing (all breakpoints)
   - [ ] Accessibility audit (WCAG 2.1 AA)
   - [ ] Performance optimization and testing
   - [ ] Security testing
   - [ ] Content review and copywriting
   - [ ] User testing with sample audience

7. **Launch Preparation**
   - [ ] Final content review
   - [ ] Set up analytics (Google Analytics 4)
   - [ ] Configure email delivery
   - [ ] Set up payment processing (if merchandise has fees)
   - [ ] Prepare social media launch strategy
   - [ ] Plan press release/announcement
   - [ ] Train team on content management

---

**Design Document Version**: 2.0  
**Last Updated**: 2026-05-11  
**Status**: Ready for Development

**Document Created By**: Claude Code  
**For**: Kadri Obafemi Hamzat Campaign 2027  
**Website**: https://kohforlakosstate.com (example domain)

---

## APPENDIX: QUICK REFERENCE

### Color Codes
- Primary Blue: #003D82 | rgb(0, 61, 130)
- Accent Gold: #FFB81C | rgb(255, 184, 28)
- Dark Gray: #1A1A1A | rgb(26, 26, 26)
- Light Gray: #F5F5F5 | rgb(245, 245, 245)
- Light Blue: #E8F0F8 | rgb(232, 240, 248)
- Success Green: #27AE60 | rgb(39, 174, 96)

### Key Dates
- Campaign Launch: January 2026
- Registration Closes: March 4, 2027
- **Election Day**: Saturday, March 11, 2027

### Platforms
- Website: kohforlakosstate.com
- Facebook: @KadriObafemiHamzat
- Twitter/X: @KOHforLagos
- Instagram: @kohforlakosstate
- TikTok: @KadriForLagos
- YouTube: Kadri Obafemi Hamzat Official
- LinkedIn: Kadri Obafemi Hamzat

### Key Contacts
- Campaign Manager: [Name] - [Email] - [Phone]
- Digital Lead: [Name] - [Email] - [Phone]
- Media Contact: [Name] - [Email] - [Phone]
- General: campaign@kohforlakosstate.com | +234-XXX-XXXX

---

**This comprehensive design document provides a complete blueprint for the campaign website development. All sections include specific details for implementation, ensuring consistency, professionalism, and effectiveness in reaching voters and advancing the campaign.**
