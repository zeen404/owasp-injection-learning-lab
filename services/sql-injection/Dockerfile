FROM python:3.12-slim

WORKDIR /app

# Install dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy application (uses render_template_string — no templates/ dir needed)
COPY app.py .

ENV PYTHONUNBUFFERED=1

EXPOSE 5000
CMD ["python", "app.py"]
