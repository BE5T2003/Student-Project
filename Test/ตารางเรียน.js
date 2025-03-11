// Constants
const days = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];
const dayNames = {
    monday: 'วันจันทร์',
    tuesday: 'วันอังคาร',
    wednesday: 'วันพุธ',
    thursday: 'วันพฤหัสบดี',
    friday: 'วันศุกร์',
    saturday: 'วันเสาร์',
    sunday: 'วันอาทิตย์'
};

// DOM Elements
const addCourseButton = document.getElementById('addCourseButton');
const courseSidebar = document.getElementById('course-sidebar');
const deleteZone = document.getElementById('deleteZone');
const timetable = document.getElementById('timetable');
const menuToggle = document.getElementById('menu-toggle');
const mainSidebar = document.getElementById('main-sidebar');
const content = document.getElementById('content');
const searchInput = document.getElementById('searchInput');

// State variables
let draggedElement = null;
let isResizing = false;
let currentSpan = 1;
let startResizeX = 0;
let originalWidth = 0;
let currentFilters = {
    faculty: '',
    program: '',
    major: '',
    year: '',
    semester: ''
};

// Sidebar toggle functionality
menuToggle.addEventListener('click', () => {
    mainSidebar.classList.toggle('collapsed');
    content.classList.toggle('expanded');
});

// Search functionality
searchInput.addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase();
    const courses = document.querySelectorAll('.course, .course-template');
    
    courses.forEach(course => {
        const courseCode = course.querySelector('.course-code').textContent.toLowerCase();
        const courseLocation = course.querySelector('.course-location').textContent.toLowerCase();
        const isVisible = courseCode.includes(searchTerm) || courseLocation.includes(searchTerm);
        
        if (course.classList.contains('course-template')) {
            course.style.display = isVisible ? 'block' : 'none';
        } else {
            course.style.opacity = isVisible ? '1' : '0.5';
        }
    });
});

// Filter initialization and handling
function initializeFilters() {
    const filterIds = ['faculty', 'program', 'major', 'year', 'semester'];

    filterIds.forEach(filterId => {
        const select = document.getElementById(`${filterId}Select`);
        if (select) {
            select.addEventListener('change', (e) => {
                currentFilters[filterId] = e.target.value;
                updateVisibleCourses();
            });
        }
    });
}

function updateVisibleCourses() {
    const courseTemplates = document.querySelectorAll('.course-template');

    courseTemplates.forEach(template => {
        const matchesFilters = shouldShowCourse(template);
        template.style.display = matchesFilters ? 'block' : 'none';
    });
}

function shouldShowCourse(courseTemplate) {
    if (Object.values(currentFilters).every(filter => filter === '')) {
        return true;
    }

    // Add default data attributes if not present in HTML
    if (!courseTemplate.dataset.faculty) {
        courseTemplate.dataset.faculty = 'sci'; // Default to science faculty
    }
    
    if (!courseTemplate.dataset.program) {
        courseTemplate.dataset.program = 'bachelor'; // Default to bachelor program
    }
    
    if (!courseTemplate.dataset.major) {
        courseTemplate.dataset.major = 'cs'; // Default to computer science
    }
    
    if (!courseTemplate.dataset.year) {
        courseTemplate.dataset.year = '1'; // Default to year 1
    }
    
    if (!courseTemplate.dataset.semester) {
        courseTemplate.dataset.semester = '1'; // Default to semester 1
    }

    const courseData = {
        faculty: courseTemplate.dataset.faculty || '',
        program: courseTemplate.dataset.program || '',
        major: courseTemplate.dataset.major || '',
        year: courseTemplate.dataset.year || '',
        semester: courseTemplate.dataset.semester || ''
    };

    return Object.keys(currentFilters).every(filter => {
        if (!currentFilters[filter]) {
            return true;
        }
        return courseData[filter] === currentFilters[filter];
    });
}

function resetFilters() {
    const filterIds = ['faculty', 'program', 'major', 'year', 'semester'];

    filterIds.forEach(filterId => {
        const select = document.getElementById(`${filterId}Select`);
        if (select) {
            select.value = '';
            currentFilters[filterId] = '';
        }
    });

    updateVisibleCourses();
}

// Course creation
function createCourse(data) {
    const course = document.createElement('div');
    course.className = 'course';
    course.draggable = true;

    course.dataset.faculty = data.faculty || '';
    course.dataset.program = data.program || '';
    course.dataset.major = data.major || '';
    course.dataset.year = data.year || '';
    course.dataset.semester = data.semester || '';

    const codeDiv = document.createElement('div');
    codeDiv.className = 'course-code';
    codeDiv.textContent = `${data.code} (${data.section})`;

    const locationDiv = document.createElement('div');
    locationDiv.className = 'course-location';
    locationDiv.textContent = data.location;

    const resizeHandle = document.createElement('div');
    resizeHandle.className = 'resize-handle';

    course.appendChild(codeDiv);
    course.appendChild(locationDiv);
    course.appendChild(resizeHandle);

    setupResizeHandling(course, resizeHandle);
    setupDragHandling(course);

    return course;
}

// Resize handling
function setupResizeHandling(course, handle) {
    let startX, startWidth, startCell, timeSlots, startIndex;
    let currentCellWidth = 0;
    let animationFrameId = null;
    const RESIZE_THRESHOLD = 0.5;

    handle.addEventListener('mousedown', initResize);

    function initResize(e) {
        e.preventDefault();
        isResizing = true;
        draggedElement = null;

        startX = e.clientX;
        startCell = course.closest('td');
        startWidth = startCell.offsetWidth * startCell.colSpan;
        currentCellWidth = startCell.offsetWidth;
        timeSlots = Array.from(startCell.parentElement.children);
        startIndex = timeSlots.indexOf(startCell);

        course.classList.add('resizing');
        document.body.style.cursor = 'col-resize';

        document.addEventListener('mousemove', handleResize);
        document.addEventListener('mouseup', stopResize);

        updateResizePreview(startIndex, startCell.colSpan);
    }

    function handleResize(e) {
        if (!isResizing) return;

        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
        }

        animationFrameId = requestAnimationFrame(() => {
            const currentX = e.clientX;
            const difference = currentX - startX;
            const newWidth = startWidth + difference;

            const rawSpan = newWidth / currentCellWidth;
            const newSpan = Math.max(1, Math.round(rawSpan));

            if (newSpan !== currentSpan && startIndex + newSpan <= timeSlots.length) {
                const canExpand = checkExpandPossibility(startIndex, newSpan);

                if (canExpand) {
                    const progress = (rawSpan % 1);

                    updateResizePreview(startIndex, newSpan, progress);

                    if (Math.abs(progress - 0.5) < RESIZE_THRESHOLD) {
                        clearTargetCells(startIndex + 1, newSpan);
                        startCell.colSpan = newSpan;
                        currentSpan = newSpan;
                    }
                }
            }
        });
    }

    function checkExpandPossibility(startIdx, span) {
        for (let i = startIdx + 1; i < startIdx + span; i++) {
            const targetCell = timeSlots[i];
            if (targetCell && targetCell.querySelector('.course:not(.resizing)')) {
                return false;
            }
        }
        return true;
    }

    function clearTargetCells(startIdx, span) {
        for (let i = startIdx; i < startIdx + span - 1; i++) {
            const targetCell = timeSlots[i];
            if (targetCell) {
                const courses = targetCell.querySelectorAll('.course:not(.resizing)');
                courses.forEach(course => course.remove());
            }
        }
    }

    function updateResizePreview(startIdx, span, progress = 0) {
        timeSlots.forEach((slot, index) => {
            if (index > startIdx) {
                const isInSpanRange = index < startIndex + span;
                const opacity = isInSpanRange ? 0.2 + (0.2 * (1 - progress)) : 0.1;
                slot.style.backgroundColor = `rgba(76, 175, 80, ${opacity})`;
                slot.style.transition = 'background-color 0.15s ease-out';
            }
        });
    }

    function stopResize() {
        isResizing = false;

        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
        }

        course.classList.remove('resizing');
        document.body.style.cursor = '';

        document.removeEventListener('mousemove', handleResize);
        document.removeEventListener('mouseup', stopResize);

        timeSlots.forEach(slot => {
            slot.style.backgroundColor = '';
            slot.style.transition = '';
        });
    }
}

// Drag handling
function setupDragHandling(course) {
    course.addEventListener('dragstart', (e) => {
        if (isResizing) {
            e.preventDefault();
            return;
        }
        draggedElement = course;
        deleteZone.classList.add('active');
        course.classList.add('dragging');

        const currentCell = course.closest('td');
        if (currentCell && currentCell.colSpan > 1) {
            currentCell.colSpan = 1;
        }
    });

    course.addEventListener('dragend', () => {
        deleteZone.classList.remove('active');
        course.classList.remove('dragging');
        draggedElement = null;
    });
}

// Initialize templates
function initializeTemplates() {
    document.querySelectorAll('.course-template').forEach(template => {
        template.addEventListener('dragstart', (e) => {
            const courseData = {
                code: template.dataset.courseCode,
                section: template.dataset.section,
                location: template.dataset.location,
                faculty: template.dataset.faculty || 'sci',
                program: template.dataset.program || 'bachelor',
                major: template.dataset.major || 'cs',
                year: template.dataset.year || '1',
                semester: template.dataset.semester || '1'
            };
            e.dataTransfer.setData('text/plain', JSON.stringify(courseData));
        });
    });
}

// Initialize drop zones
function initializeDropZones() {
    document.querySelectorAll('.droppable').forEach(cell => {
        cell.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (!cell.querySelector('.course')) {
                cell.classList.add('highlight');
            }
        });

        cell.addEventListener('dragleave', () => {
            cell.classList.remove('highlight');
        });

        cell.addEventListener('drop', handleDrop);
    });
}

// Handle course dropping
function handleDrop(e) {
    e.preventDefault();
    const cell = e.target.closest('td');
    cell.classList.remove('highlight');

    if (cell.querySelector('.course')) {
        return;
    }

    if (draggedElement) {
        cell.appendChild(draggedElement);
        currentSpan = 1;
    } else {
        try {
            const courseData = JSON.parse(e.dataTransfer.getData('text/plain'));
            const newCourse = createCourse(courseData);
            cell.appendChild(newCourse);
        } catch (error) {
            console.error('Error creating course:', error);
        }
    }
}

// Initialize delete zone
function initializeDeleteZone() {
    deleteZone.addEventListener('dragover', (e) => e.preventDefault());
    deleteZone.addEventListener('drop', (e) => {
        e.preventDefault();
        if (draggedElement) {
            draggedElement.remove();
        }
        deleteZone.classList.remove('active');
    });
}

// Add course button functionality
addCourseButton.addEventListener('click', () => {
    courseSidebar.classList.toggle('open');
    if (courseSidebar.classList.contains('open')) {
        courseSidebar.style.display = 'block';
        resetFilters();
    } else {
        // เพิ่ม transition delay เพื่อให้ animation เล่นจบก่อนซ่อน
        setTimeout(() => {
            if (!courseSidebar.classList.contains('open')) {
                courseSidebar.style.display = 'none';
            }
        }, 300); // 300ms ตาม transition duration
    }
});

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initializeTemplates();
    initializeDropZones();
    initializeDeleteZone();
    initializeFilters();
});

// Sidebar toggle functionality
if (menuToggle) {
    menuToggle.addEventListener('click', () => {
        mainSidebar.classList.toggle('collapsed'); // เปิด-ปิด sidebar
        content.classList.toggle('expanded'); // ขยับเนื้อหาตาม
    });
}

        