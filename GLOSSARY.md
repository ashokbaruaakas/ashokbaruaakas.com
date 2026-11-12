# Portfolio

The owner's portfolio site and the tools behind it for writing resumes and running a job hunt.

## Language

### Resumes

**Portfolio**:
The owner's base content: profile, experience, projects, education and skills. Every Resume is drawn from it.
_Avoid_: Master resume, base resume

**Resume**:
A named configuration over the Portfolio: a Selection, Overrides and a Design. It is not a copy of the content.
_Avoid_: Version, variant

**Public Resume**:
The one Resume whose content the resume page shows and whose PDF visitors download.
_Avoid_: Main resume, default resume

**Resolved Resume**:
The content of a Resume after its Selection and Overrides are applied to the Portfolio.

**Resume Page**:
The public web page at `/resume`: the Public Resume's content laid out for the screen. It does not use a Template and need not look like the PDF.

**Template**:
A fixed paper layout that renders a Resolved Resume to the PDF. Classic and Compact are single-column and ATS-safe; Sidebar has two columns.
_Avoid_: Theme

**Design**:
How a Resume's PDF looks: its Template, a colour and a font. An unset colour is Emerald; an unset font follows the Template's own.

**Selection**:
Which Portfolio items a Resume includes, and in what order.

**Override**:
Text that replaces Portfolio text within one Resume: the headline, the summary, or an experience's whole list of highlights.

**Highlight**:
One bullet under an experience.
_Avoid_: Bullet, achievement

**Build**:
One rendered PDF of a Resume. A Resume keeps only its current Build, besides its Sent Builds.
_Avoid_: Version, export

**Sent Build**:
The Build recorded as sent with a job application, kept as it was sent.

**Tailored Resume**:
A Resume made for one job application. Tailoring a Resume from a job starts it as a copy of the Public Resume.

**Archived Resume**:
A Resume kept only for its Sent Builds: hidden from the owner's list and never rebuilt.
