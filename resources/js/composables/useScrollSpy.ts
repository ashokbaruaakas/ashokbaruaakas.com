import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { PortfolioSection } from '@/types/portfolio';

export const portfolioSections: PortfolioSection[] = [
    { id: 'hero', label: 'Home' },
    { id: 'about', label: 'About' },
    { id: 'skills', label: 'Skills' },
    { id: 'projects', label: 'Projects' },
    { id: 'experience', label: 'Experience' },
    { id: 'connect', label: 'Connect' },
];

export function useScrollSpy(sections: PortfolioSection[] = portfolioSections) {
    const activeId = ref(sections[0]?.id ?? '');

    let ticking = false;

    const update = () => {
        ticking = false;

        const offset = window.innerHeight / 3;
        let current = sections[0]?.id ?? '';

        for (const section of sections) {
            const element = document.getElementById(section.id);

            if (element && element.getBoundingClientRect().top <= offset) {
                current = section.id;
            }
        }

        activeId.value = current;
    };

    const onScroll = () => {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(update);
        }
    };

    onMounted(() => {
        update();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('scroll', onScroll);
        window.removeEventListener('resize', onScroll);
    });

    function scrollTo(id: string) {
        document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
    }

    return { activeId, scrollTo };
}
