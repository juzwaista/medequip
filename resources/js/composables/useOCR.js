import { createWorker } from 'tesseract.js';

export function useOCR() {
    const scanImage = async (imageFile, language = 'eng') => {
        const worker = await createWorker(language);
        const { data: { text } } = await worker.recognize(imageFile);
        await worker.terminate();
        return text;
    };

    const extractExpirationDate = (text) => {
        // Regex to look for patterns like MM/DD/YYYY, DD/MM/YYYY, or Month DD, YYYY
        // Common on licenses: "Expiry Date:", "Valid Until:", etc.
        const datePatterns = [
            /\b(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})\b/g, // MM/DD/YYYY
            /\b(January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{1,2},?\s+\d{4}\b/gi,
        ];

        let foundDates = [];
        for (const pattern of datePatterns) {
            const matches = text.match(pattern);
            if (matches) foundDates.push(...matches);
        }

        // Return the furthest date found (likely the expiry)
        if (foundDates.length > 0) {
            const parsedDates = foundDates.map(d => new Date(d)).filter(d => !isNaN(d.getTime()));
            if (parsedDates.length > 0) {
                const furthestDate = new Date(Math.max(...parsedDates));
                return furthestDate.toISOString().split('T')[0]; // YYYY-MM-DD
            }
        }

        return null;
    };

    const searchNameInText = (name, text) => {
        if (!name || !text) return false;
        
        // Normalize name and text
        const cleanName = name.toLowerCase().replace(/[^a-z0-9 ]/g, '');
        const cleanText = text.toLowerCase().replace(/[^a-z0-9 ]/g, '');
        
        // Simple word-by-word matching
        const nameWords = cleanName.split(/\s+/).filter(w => w.length > 2);
        if (nameWords.length === 0) return false;

        const matchedWords = nameWords.filter(word => cleanText.includes(word));
        
        // If more than 60% of significant name words match, consider it a hit
        return (matchedWords.length / nameWords.length) >= 0.6;
    };

    return {
        scanImage,
        extractExpirationDate,
        searchNameInText
    };
}
