import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { PortfolioSection } from '@/types/portfolio';

export const portfolioSections: PortfolioSection[] = [
    { id: 'hero', label: 'Home' },
    { id: 'about', label: 'About' },
    { id: 'skills', label: 'Skills' },
    { id: 'work', label: 'Work' },
    { id: 'experience', label: 'Experience' },
    { id: 'education', label: 'Education' },
    { id: 'connect', label: 'Connect' },
];

export function useScrollSpy(sections: PortfolioSection[] = portfolioSections) {
    const activeId = ref(sections[0]?.id ?? '');

    let ticking = false;
    let locked = false;
    let unlockTimer: ReturnType<typeof setTimeout> | undefined;

    const update = () => {
        ticking = false;

        if (locked) {
            return;
        }

        const offset = window.innerHeight / 3;
        let current = sections[0]?.id ?? '';

        for (const section of sections) {
            const element = document.getElementById(section.id);

            if (element && element.getBoundingClientRect().top <= offset) {
                current = section.id;
            }
        }

        const lastSection = sections[sections.length - 1];

        if (
            lastSection &&
            window.scrollY + window.innerHeight >=
                document.documentElement.scrollHeight - 4
        ) {
            current = lastSection.id;
        }

        activeId.value = current;
    };

    const onScroll = () => {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(update);
        }
    };

    const unlock = () => {
        locked = false;
        window.removeEventListener('scrollend', unlock);
    };

    onMounted(() => {
        update();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('scroll', onScroll);
        window.removeEventListener('resize', onScroll);
        window.removeEventListener('scrollend', unlock);
        clearTimeout(unlockTimer);
    });

    function scrollTo(id: string) {
        const element = document.getElementById(id);

        if (!element) {
            return;
        }

        activeId.value = id;
        locked = true;
        clearTimeout(unlockTimer);
        window.addEventListener('scrollend', unlock, { once: true });
        unlockTimer = setTimeout(unlock, 800);

        element.scrollIntoView({ behavior: 'smooth' });
    }

    return { activeId, scrollTo };
}
